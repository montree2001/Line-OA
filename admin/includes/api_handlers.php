<?php
/**
 * api_handlers.php - ฟังก์ชันสำหรับจัดการคำขอ API
 * 
 * ไฟล์นี้รวมฟังก์ชันสำหรับจัดการคำขอ API ต่างๆ ของระบบ
 */

/**
 * ฟังก์ชันจัดการคำขอ API
 */
function handleApiRequest() {
    // กำหนดค่าเริ่มต้นให้ตัวแปร $response
    $response = ['success' => false, 'message' => 'ไม่พบการดำเนินการ'];
    
    // ตรวจสอบประเภทคำขอ (POST/GET)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePostRequest();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest();
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * ฟังก์ชันจัดการคำขอแบบ POST
 */
function handlePostRequest() {
    header('Content-Type: application/json; charset=UTF-8');
    $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
    
    try {
        // ตรวจสอบว่ามี action หรือไม่
        if (!isset($_POST['action'])) {
            echo json_encode(['success' => false, 'message' => 'ไม่ระบุการดำเนินการ'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // ดำเนินการตาม action ที่ระบุ
        switch ($_POST['action']) {
            case 'add_class':
                $response = addClass($_POST);
                break;
                
            case 'update_class':
            case 'edit_class':
                $response = updateClass($_POST);
                break;
                
            case 'delete_class':
                $response = deleteClass($_POST['class_id']);
                break;
                
            case 'add_department':
                $response = addDepartment($_POST);
                break;
                
            case 'update_department':
            case 'edit_department':
                $response = updateDepartment($_POST);
                break;
                
            case 'delete_department':
                $response = deleteDepartment($_POST['department_id']);
                break;
                
            case 'manage_advisors':
                $changes = json_decode($_POST['changes'] ?? '[]', true);
                $response = updateClassAdvisors($_POST['class_id'], $changes);
                break;
                
            case 'promote_students':
                $response = promoteStudents([
                    'from_academic_year_id' => $_POST['from_academic_year_id'],
                    'to_academic_year_id' => $_POST['to_academic_year_id'],
                    'notes' => $_POST['notes'] ?? '',
                    'admin_id' => $_SESSION['user_id'] ?? 1
                ]);
                break;
                
            default:
                $response = ['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        error_log("Error in API POST request: " . $e->getMessage());
    }
    
    // ส่ง response กลับไป
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * ฟังก์ชันจัดการคำขอแบบ GET
 */
function handleGetRequest() {
    header('Content-Type: application/json; charset=UTF-8');
    $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
    
    try {
        // ตรวจสอบว่ามี action หรือไม่
        if (!isset($_GET['action'])) {
            echo json_encode(['success' => false, 'message' => 'ไม่ระบุการดำเนินการ'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // ดำเนินการตาม action ที่ระบุ
        switch ($_GET['action']) {
            case 'get_department':
                $response = getDepartmentDetails($_GET['department_id']);
                break;
                
            case 'get_class':
                $response = getClassDetails($_GET['class_id']);
                break;
                
            case 'get_class_details':
                $response = getDetailedClassInfo($_GET['class_id']);
                break;
                
            case 'get_class_advisors':
                $response = getClassAdvisors($_GET['class_id']);
                break;
                
            case 'get_class_statistics':
                $response = getClassStatistics();
                break;
                
            case 'download_report':
                downloadClassReport($_GET['class_id'], $_GET['type'] ?? 'full');
                exit; // ฟังก์ชันนี้จัดการ output เอง
                
            default:
                $response = ['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        error_log("Error in API GET request: " . $e->getMessage());
    }
    
    // ส่ง response กลับไป
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * ดึงข้อมูลสถิติของชั้นเรียนทั้งหมด
 */
function getClassStatistics() {
    global $conn;
    
    if (!isset($conn)) {
        return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'];
    }
    
    try {
        // ดึงข้อมูลจำนวนนักเรียนตามระดับชั้น
        $stmt = $conn->prepare("
            SELECT 
                c.level, 
                COUNT(DISTINCT s.student_id) as student_count
            FROM classes c
            JOIN students s ON c.class_id = s.current_class_id
            WHERE s.status = 'กำลังศึกษา'
            GROUP BY c.level
            ORDER BY c.level
        ");
        $stmt->execute();
        $levelStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลจำนวนนักเรียนตามแผนกวิชา
        $stmt = $conn->prepare("
            SELECT 
                d.department_name, 
                COUNT(DISTINCT s.student_id) as student_count
            FROM departments d
            JOIN classes c ON d.department_id = c.department_id
            JOIN students s ON c.class_id = s.current_class_id
            WHERE s.status = 'กำลังศึกษา'
            GROUP BY d.department_id
            ORDER BY student_count DESC
        ");
        $stmt->execute();
        $departmentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลสถิติการเข้าแถวโดยรวม
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status != 'present' THEN a.student_id END) as absent_count
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            WHERE a.date >= CURDATE() - INTERVAL 30 DAY
        ");
        $stmt->execute();
        $attendanceStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM risk_students
            WHERE risk_level IN ('high', 'critical')
        ");
        $stmt->execute();
        $atRiskCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return [
            'success' => true,
            'level_stats' => $levelStats,
            'department_stats' => $departmentStats,
            'attendance_stats' => $attendanceStats,
            'at_risk_count' => $atRiskCount
        ];
    } catch (PDOException $e) {
        error_log("Error in getClassStatistics: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลชั้นเรียนตัวอย่างสำหรับการทดสอบ
 */
function getSampleClasses() {
    return [
        [
            'class_id' => 1,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '1',
            'attendance_rate' => 94.3,
            'student_count' => 35,
            'advisors' => [
                ['id' => 1, 'name' => 'นาย มนตรี ศรีสุข', 'is_primary' => true]
            ]
        ],
        [
            'class_id' => 2,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '2',
            'attendance_rate' => 87.5,
            'student_count' => 32,
            'advisors' => [
                ['id' => 2, 'name' => 'นาง ราตรี นอนดึก', 'is_primary' => true]
            ]
        ],
        [
            'class_id' => 3,
            'academic_year_id' => 1,
            'level' => 'ปวช.2',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '1',
            'attendance_rate' => 92.0,
            'student_count' => 30,
            'advisors' => [
                ['id' => 3, 'name' => 'นาย มานะ พยายาม', 'is_primary' => true]
            ]
        ],
        [
            'class_id' => 4,
            'academic_year_id' => 1,
            'level' => 'ปวช.3',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '1',
            'attendance_rate' => 95.5,
            'student_count' => 28,
            'advisors' => [
                ['id' => 4, 'name' => 'นางสาว วันดี สดใส', 'is_primary' => true]
            ]
        ],
        [
            'class_id' => 5,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'ช่างยนต์',
            'department_name' => 'ช่างยนต์',
            'group_number' => '1',
            'attendance_rate' => 82.0,
            'student_count' => 38,
            'advisors' => [
                ['id' => 5, 'name' => 'นาง สมศรี ใจดี', 'is_primary' => true]
            ]
        ]
    ];
}

/**
 * ดึงข้อมูลแผนกวิชาตัวอย่างสำหรับการทดสอบ
 */
function getSampleDepartments() {
    return [
        'IT' => ['name' => 'เทคโนโลยีสารสนเทศ', 'student_count' => 110, 'class_count' => 4, 'teacher_count' => 6],
        'AUTO' => ['name' => 'ช่างยนต์', 'student_count' => 120, 'class_count' => 4, 'teacher_count' => 8],
        'MECH' => ['name' => 'ช่างกลโรงงาน', 'student_count' => 80, 'class_count' => 3, 'teacher_count' => 5],
        'ELEC' => ['name' => 'ช่างไฟฟ้ากำลัง', 'student_count' => 90, 'class_count' => 3, 'teacher_count' => 6],
        'ACC' => ['name' => 'การบัญชี', 'student_count' => 70, 'class_count' => 3, 'teacher_count' => 4],
        'GEN' => ['name' => 'สามัญ', 'student_count' => 0, 'class_count' => 0, 'teacher_count' => 12]
    ];
}

/**
 * ดึงข้อมูลปีการศึกษาตัวอย่างสำหรับการทดสอบ
 */
function getSampleAcademicYears() {
    return [
        'academic_years' => [
            ['academic_year_id' => 1, 'year' => '2568', 'semester' => '1', 'is_active' => 1],
            ['academic_year_id' => 2, 'year' => '2567', 'semester' => '2', 'is_active' => 0],
            ['academic_year_id' => 3, 'year' => '2567', 'semester' => '1', 'is_active' => 0]
        ],
        'has_new_academic_year' => true,
        'current_academic_year' => '2567 ภาคเรียนที่ 2',
        'next_academic_year' => '2568 ภาคเรียนที่ 1',
        'active_year_id' => 1
    ];
}

/**
 * ดึงข้อมูลครูตัวอย่างสำหรับการทดสอบ
 */
function getSampleTeachers() {
    return [
        ['teacher_id' => 1, 'title' => 'นาย', 'first_name' => 'ใจดี', 'last_name' => 'มากเมตตา'],
        ['teacher_id' => 2, 'title' => 'นาง', 'first_name' => 'ราตรี', 'last_name' => 'นอนดึก'],
        ['teacher_id' => 3, 'title' => 'นาย', 'first_name' => 'มานะ', 'last_name' => 'พยายาม'],
        ['teacher_id' => 4, 'title' => 'นางสาว', 'first_name' => 'วันดี', 'last_name' => 'สดใส'],
        ['teacher_id' => 5, 'title' => 'นาง', 'first_name' => 'สมศรี', 'last_name' => 'ใจดี']
    ];
}

/**
 * ดึงข้อมูลการเลื่อนชั้นตัวอย่างสำหรับการทดสอบ
 */
function getSamplePromotionCounts() {
    return [
        ['current_level' => 'ปวช.1', 'student_count' => 120, 'new_level' => 'ปวช.2'],
        ['current_level' => 'ปวช.2', 'student_count' => 105, 'new_level' => 'ปวช.3'],
        ['current_level' => 'ปวช.3', 'student_count' => 95, 'new_level' => 'สำเร็จการศึกษา'],
        ['current_level' => 'ปวส.1', 'student_count' => 80, 'new_level' => 'ปวส.2'],
        ['current_level' => 'ปวส.2', 'student_count' => 75, 'new_level' => 'สำเร็จการศึกษา']
    ];
}
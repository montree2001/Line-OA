<?php
/**
 * api/reports/index.php - API endpoint for generating reports
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 * 
 * This API handles generating and downloading reports.
 */

// Include necessary files
require_once '../../db_connect.php';

// Check user session and permissions
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Process API request
try {
    // Get database connection
    $db = getDB();
    
    // Get the request method and payload
    $method = $_SERVER['REQUEST_METHOD'];
    $action = '';
    
    // Parse URL to determine action
    $requestUri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/api\/reports\/(.+)/', $requestUri, $matches)) {
        $action = $matches[1];
    }
    
    // Get request data
    if ($method === 'POST') {
        $requestData = json_decode(file_get_contents('php://input'), true);
    } else {
        $requestData = $_GET;
    }
    
    // Process based on action
    switch ($action) {
        case 'at-risk':
            generateAtRiskReport($db, $requestData);
            break;
        default:
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Report type not found']);
            break;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Generate a report of at-risk students
 * 
 * @param PDO $db Database connection
 * @param array $data Request data
 */
function generateAtRiskReport($db, $data) {
    // Get filters from request data
    $filters = isset($data['filters']) ? $data['filters'] : [];
    $academicYearId = isset($data['academic_year_id']) ? $data['academic_year_id'] : null;
    
    // If no academic year specified, get the active one
    if (!$academicYearId) {
        $stmt = $db->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1");
        $stmt->execute();
        $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($academicYear) {
            $academicYearId = $academicYear['academic_year_id'];
        } else {
            // Get latest academic year as fallback
            $stmt = $db->prepare("SELECT academic_year_id FROM academic_years ORDER BY year DESC, semester DESC LIMIT 1");
            $stmt->execute();
            $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($academicYear) {
                $academicYearId = $academicYear['academic_year_id'];
            } else {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['error' => 'No academic year found']);
                exit;
            }
        }
    }
    
    // Build SQL query with filters
    $sql = "
        SELECT s.student_id, s.student_code, u.first_name, u.last_name, u.phone_number,
               c.level, c.group_number, d.department_name,
               sar.total_attendance_days, sar.total_absence_days,
               CONCAT(tu.first_name, ' ', tu.last_name) as advisor_name,
               tu.phone_number as advisor_phone,
               pu.first_name as parent_first_name, pu.last_name as parent_last_name,
               pu.phone_number as parent_phone, p.relationship as parent_relationship
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN student_academic_records sar ON s.student_id = sar.student_id
        JOIN classes c ON s.current_class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
        LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
        LEFT JOIN users tu ON t.user_id = tu.user_id
        LEFT JOIN parent_student_relation psr ON s.student_id = psr.student_id
        LEFT JOIN parents p ON psr.parent_id = p.parent_id
        LEFT JOIN users pu ON p.user_id = pu.user_id
        WHERE sar.academic_year_id = :academic_year_id
        AND s.status = 'กำลังศึกษา'
        AND (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80
    ";
    
    $params = [':academic_year_id' => $academicYearId];
    
    // Apply filters
    if (!empty($filters['department_id'])) {
        $sql .= " AND d.department_id = :department_id";
        $params[':department_id'] = $filters['department_id'];
    }
    
    if (!empty($filters['class_level'])) {
        $sql .= " AND c.level = :class_level";
        $params[':class_level'] = $filters['class_level'];
    }
    
    if (!empty($filters['class_room'])) {
        $sql .= " AND c.group_number = :class_room";
        $params[':class_room'] = $filters['class_room'];
    }
    
    if (!empty($filters['advisor'])) {
        $sql .= " AND t.teacher_id = :advisor";
        $params[':advisor'] = $filters['advisor'];
    }
    
    if (!empty($filters['min_attendance']) && !empty($filters['max_attendance'])) {
        $sql .= " AND (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 BETWEEN :min_attendance AND :max_attendance";
        $params[':min_attendance'] = $filters['min_attendance'];
        $params[':max_attendance'] = $filters['max_attendance'];
    } else if (!empty($filters['min_attendance'])) {
        $sql .= " AND (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 >= :min_attendance";
        $params[':min_attendance'] = $filters['min_attendance'];
    } else if (!empty($filters['max_attendance'])) {
        $sql .= " AND (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 <= :max_attendance";
        $params[':max_attendance'] = $filters['max_attendance'];
    }
    
    // Order results
    $sql .= " ORDER BY d.department_name, c.level, c.group_number, (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days)))";
    
    // Prepare and execute the statement
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no students found, return an error
    if (empty($students)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'No students found matching the criteria']);
        exit;
    }
    
    // Get current academic year info for report title
    $stmt = $db->prepare("SELECT year, semester FROM academic_years WHERE academic_year_id = :academic_year_id");
    $stmt->execute([':academic_year_id' => $academicYearId]);
    $academicYearInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create CSV data
    $csvData = [];
    
    // Add header row
    $csvData[] = [
        'ลำดับ',
        'รหัสนักศึกษา',
        'ชื่อ-นามสกุล',
        'ระดับชั้น',
        'กลุ่ม',
        'แผนกวิชา',
        'อัตราการเข้าแถว (%)',
        'วันที่เข้าแถว',
        'วันที่ขาด',
        'วันทั้งหมด',
        'ครูที่ปรึกษา',
        'เบอร์โทรครู',
        'ผู้ปกครอง',
        'ความสัมพันธ์',
        'เบอร์โทรผู้ปกครอง'
    ];
    
    // Add data rows
    $i = 1;
    foreach ($students as $student) {
        $totalDays = $student['total_attendance_days'] + $student['total_absence_days'];
        $attendanceRate = $totalDays > 0 ? 
            round(($student['total_attendance_days'] / $totalDays) * 100, 1) : 0;
        
        // Process Thai title prefixes for names
        $studentName = processThaiName($student['first_name'], $student['last_name']);
        $parentName = $student['parent_first_name'] ? 
            processThaiName($student['parent_first_name'], $student['parent_last_name']) : 'ไม่ระบุ';
        
        $csvData[] = [
            $i++,
            $student['student_code'],
            $studentName,
            $student['level'],
            $student['group_number'],
            $student['department_name'],
            $attendanceRate,
            $student['total_attendance_days'],
            $student['total_absence_days'],
            $totalDays,
            $student['advisor_name'] ?: 'ไม่ระบุ',
            $student['advisor_phone'] ?: 'ไม่ระบุ',
            $parentName,
            $student['parent_relationship'] ?: 'ไม่ระบุ',
            $student['parent_phone'] ?: 'ไม่ระบุ'
        ];
    }
    
    // Set headers for CSV download
    $filename = "รายงานนักเรียนเสี่ยงตกกิจกรรม-ปีการศึกษา" . 
               $academicYearInfo['year'] . "-ภาคเรียนที่" . 
               $academicYearInfo['semester'] . "-" . 
               date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Add BOM for UTF-8 encoding in Excel
    echo "\xEF\xBB\xBF";
    
    // Output CSV data
    $output = fopen('php://output', 'w');
    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

/**
 * Process Thai name with proper title prefix
 * 
 * @param string $firstName First name
 * @param string $lastName Last name
 * @return string Formatted name
 */
function processThaiName($firstName, $lastName) {
    // Check if name already has a title prefix
    if (mb_substr($firstName, 0, 3, 'UTF-8') === 'นาย' ||
        mb_substr($firstName, 0, 9, 'UTF-8') === 'นางสาว' ||
        mb_substr($firstName, 0, 6, 'UTF-8') === 'นาง') {
        return $firstName . ' ' . $lastName;
    } else {
        // Add appropriate title
        $title = (mb_substr($firstName, 0, 1, 'UTF-8') === 'เ') ? 'นางสาว' : 'นาย';
        return $title . $firstName . ' ' . $lastName;
    }
}
?>
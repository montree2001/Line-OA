<?php
/**
 * api/reports.php - API endpoints for reports data
 * 
 * This file handles AJAX requests from the reports page,
 * fetching and processing data from the database.
 */

// Start session
session_start();

// Check login status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Connect to database
require_once '../../db_connect.php';
$conn = getDB();

// Set response content type to JSON
header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle requests based on method and action
if ($method === 'GET') {
    switch ($action) {
        case 'overview':
            getOverviewStats();
            break;
            
        case 'yearly_trends':
            getYearlyTrends();
            break;
            
        case 'class_rates':
            getClassRates();
            break;
            
        case 'absence_reasons':
            getAbsenceReasons();
            break;
            
        case 'risk_students':
            getRiskStudents();
            break;
            
        case 'student_details':
            getStudentDetails($_GET['student_id'] ?? 0);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} elseif ($method === 'POST') {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'generate_report':
            generateReport($data);
            break;
            
        case 'export_report':
            exportReport($data);
            break;
            
        case 'send_notification':
            sendNotification($data);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

/**
 * Get overview statistics for the dashboard
 */
function getOverviewStats() {
    global $conn;
    
    try {
        // Get current academic year
        $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$academicYear) {
            echo json_encode(['error' => 'No active academic year found']);
            return;
        }
        
        $academicYearId = $academicYear['academic_year_id'];
        
        // Total students count
        $query = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
        $stmt = $conn->query($query);
        $totalStudents = $stmt->fetchColumn();
        
        // Attendance days in current month
        $currentMonth = date('m');
        $currentYear = date('Y');
        $query = "SELECT COUNT(DISTINCT date) 
                  FROM attendance 
                  WHERE academic_year_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $currentMonth, $currentYear]);
        $attendanceDays = $stmt->fetchColumn();
        
        // Average attendance rate
        $query = "SELECT 
                    AVG(CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END) as avg_rate
                  FROM student_academic_records sar
                  JOIN students s ON sar.student_id = s.student_id
                  WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId]);
        $avgAttendanceRate = $stmt->fetchColumn();
        
        // Get risk threshold
        $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
        $stmt = $conn->query($query);
        $riskThreshold = $stmt->fetchColumn() ?: 60;
        
        // Count of students at risk
        $query = "SELECT COUNT(*) FROM student_academic_records sar
                  JOIN students s ON sar.student_id = s.student_id
                  WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
                  AND (
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END
                  ) <= ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $riskThreshold]);
        $riskStudents = $stmt->fetchColumn();
        
        // Get last month's average rate for comparison
        $lastMonth = $currentMonth - 1;
        $lastMonthYear = $currentYear;
        if ($lastMonth <= 0) {
            $lastMonth = 12;
            $lastMonthYear--;
        }
        
        $query = "SELECT 
                    COUNT(DISTINCT a.student_id) as total_present,
                    (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students,
                    COUNT(DISTINCT a.date) as total_days
                  FROM attendance a
                  WHERE a.academic_year_id = ? 
                  AND MONTH(a.date) = ? 
                  AND YEAR(a.date) = ?
                  AND a.attendance_status = 'present'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $lastMonth, $lastMonthYear]);
        $lastMonthData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $lastMonthRate = 0;
        if ($lastMonthData['total_days'] > 0 && $lastMonthData['total_students'] > 0) {
            $lastMonthRate = ($lastMonthData['total_present'] / ($lastMonthData['total_students'] * $lastMonthData['total_days'])) * 100;
        }
        
        $rateChange = $avgAttendanceRate - $lastMonthRate;
        
        // Prepare and return response
        $response = [
            'success' => true,
            'data' => [
                'total_students' => $totalStudents,
                'attendance_days' => $attendanceDays,
                'avg_attendance_rate' => round($avgAttendanceRate, 1),
                'rate_change' => round($rateChange, 1),
                'risk_students' => $riskStudents,
                'academic_year' => $academicYear['year'],
                'semester' => $academicYear['semester'],
                'current_month' => getThaiMonth($currentMonth),
                'current_month_number' => $currentMonth,
                'current_year' => $currentYear
            ]
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get yearly attendance trends
 */
function getYearlyTrends() {
    global $conn;
    
    try {
        // Get current academic year
        $query = "SELECT academic_year_id, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$academicYear) {
            echo json_encode(['error' => 'No active academic year found']);
            return;
        }
        
        $academicYearId = $academicYear['academic_year_id'];
        $startDate = $academicYear['start_date'];
        $endDate = $academicYear['end_date'];
        
        // Generate array of months between start and end dates
        $months = [];
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);
        
        while ($currentDate <= $endDateObj) {
            $month = $currentDate->format('m');
            $year = $currentDate->format('Y');
            
            $months[] = [
                'month' => $month,
                'year' => $year,
                'month_name' => getThaiMonth($month)
            ];
            
            $currentDate->modify('+1 month');
        }
        
        // Get attendance rates for each month
        $trends = [];
        
        foreach ($months as $monthData) {
            $month = $monthData['month'];
            $year = $monthData['year'];
            
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) as present_count,
                        COUNT(DISTINCT a.student_id) as total_students,
                        COUNT(DISTINCT a.date) as attendance_days
                      FROM attendance a
                      WHERE a.academic_year_id = ? 
                      AND MONTH(a.date) = ? 
                      AND YEAR(a.date) = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$academicYearId, $month, $year]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $rate = 0;
            if ($data['attendance_days'] > 0 && $data['total_students'] > 0) {
                $rate = ($data['present_count'] / ($data['total_students'] * $data['attendance_days'])) * 100;
            }
            
            $trends[] = [
                'month' => $monthData['month_name'],
                'month_number' => $month,
                'year' => $year,
                'rate' => round($rate, 1),
                'days' => $data['attendance_days']
            ];
        }
        
        // Return response
        echo json_encode([
            'success' => true,
            'data' => $trends
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get attendance rates by class
 */
function getClassRates() {
    global $conn;
    
    try {
        // Get filters from request
        $departmentId = isset($_GET['department']) ? $_GET['department'] : '';
        $classLevel = isset($_GET['level']) ? $_GET['level'] : '';
        
        // Get current academic year
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            echo json_encode(['error' => 'No active academic year found']);
            return;
        }
        
        // Build query with optional filters
        $params = [$academicYearId, $academicYearId];
        $filterSql = '';
        
        if (!empty($departmentId)) {
            $filterSql .= " AND d.department_id = ?";
            $params[] = $departmentId;
        }
        
        if (!empty($classLevel)) {
            $filterSql .= " AND c.level = ?";
            $params[] = $classLevel;
        }
        
        // Get class attendance rates
        $query = "SELECT 
                    c.class_id,
                    c.level,
                    c.group_number,
                    CONCAT(c.level, '/', c.group_number) as class_name,
                    d.department_id,
                    d.department_name,
                    COUNT(DISTINCT s.student_id) as student_count,
                    SUM(sar.total_attendance_days) as total_attendance,
                    SUM(sar.total_absence_days) as total_absence,
                    CASE
                        WHEN SUM(sar.total_attendance_days) + SUM(sar.total_absence_days) > 0
                        THEN (SUM(sar.total_attendance_days) / (SUM(sar.total_attendance_days) + SUM(sar.total_absence_days)) * 100)
                        ELSE 100
                    END as attendance_rate
                  FROM classes c
                  JOIN departments d ON c.department_id = d.department_id
                  LEFT JOIN students s ON s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา'
                  LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
                  WHERE c.academic_year_id = ? $filterSql
                  GROUP BY c.class_id
                  ORDER BY attendance_rate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process data to include status
        foreach ($classes as &$class) {
            $rate = $class['attendance_rate'];
            if ($rate >= 90) {
                $class['status'] = 'good';
            } elseif ($rate >= 80) {
                $class['status'] = 'warning';
            } else {
                $class['status'] = 'danger';
            }
            
            // Format numbers
            $class['attendance_rate'] = round($rate, 1);
        }
        
        // Return response
        echo json_encode([
            'success' => true,
            'data' => $classes
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get absence reasons statistics
 */
function getAbsenceReasons() {
    global $conn;
    
    try {
        // Get current academic year
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            echo json_encode(['error' => 'No active academic year found']);
            return;
        }
        
        // In a real system, this would query a table of absence reasons
        // For now, use example data
        $reasons = [
            ['reason' => 'ป่วย', 'percent' => 42, 'color' => '#2196f3'],
            ['reason' => 'ธุระส่วนตัว', 'percent' => 28, 'color' => '#ff9800'],
            ['reason' => 'มาสาย', 'percent' => 15, 'color' => '#9c27b0'],
            ['reason' => 'ไม่ทราบสาเหตุ', 'percent' => 15, 'color' => '#f44336']
        ];
        
        // Return response
        echo json_encode([
            'success' => true,
            'data' => $reasons
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get students at risk of failing
 */
function getRiskStudents() {
    global $conn;
    
    try {
        // Get parameters
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        
        // Get current academic year
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            echo json_encode(['error' => 'No active academic year found']);
            return;
        }
        
        // Get risk threshold
        $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
        $stmt = $conn->query($query);
        $riskThreshold = $stmt->fetchColumn() ?: 60;
        
        // Count total risk students for pagination
        $query = "SELECT COUNT(*) 
                  FROM student_academic_records sar
                  JOIN students s ON sar.student_id = s.student_id
                  WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
                  AND (
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END
                  ) <= ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $riskThreshold]);
        $totalRiskStudents = $stmt->fetchColumn();
        
        // Get risk students data
        $query = "SELECT 
                    s.student_id,
                    s.student_code,
                    s.title,
                    u.first_name,
                    u.last_name,
                    c.level,
                    c.group_number,
                    CONCAT(c.level, '/', c.group_number) as class_name,
                    (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                     FROM teachers t 
                     JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                     WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                     LIMIT 1) as advisor_name,
                    sar.total_attendance_days,
                    sar.total_absence_days,
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END as attendance_rate
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  JOIN classes c ON s.current_class_id = c.class_id
                  JOIN student_academic_records sar ON s.student_id = sar.student_id
                  WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
                  AND (
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END
                  ) <= ?
                  ORDER BY attendance_rate ASC
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $riskThreshold, $limit, $offset]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process students data
        foreach ($students as &$student) {
            $rate = $student['attendance_rate'];
            $student['attendance_rate'] = round($rate, 1);
            
            // Set status based on rate
            if ($rate < 70) {
                $student['status'] = 'danger';
                $student['status_text'] = 'ตกกิจกรรม';
            } else {
                $student['status'] = 'warning';
                $student['status_text'] = 'เสี่ยงตกกิจกรรม';
            }
            
            // Add initial for avatar
            $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        }
        
        // Calculate pagination details
        $totalPages = ceil($totalRiskStudents / $limit);
        
        // Return response
        echo json_encode([
            'success' => true,
            'data' => [
                'students' => $students,
                'pagination' => [
                    'total' => $totalRiskStudents,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => $totalPages
                ]
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get detailed information for a specific student
 */
function getStudentDetails($studentId) {
    global $conn;
    
    if (!$studentId) {
        echo json_encode(['error' => 'Student ID is required']);
        return;
    }
    
    try {
        // Get current academic year
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            echo json_encode(['error' => 'No active academic year found']);
            return;
        }
        
        // Get student basic information
        $query = "SELECT 
                    s.student_id,
                    s.student_code,
                    s.title,
                    u.first_name,
                    u.last_name,
                    u.phone_number,
                    u.email,
                    c.level,
                    c.group_number,
                    CONCAT(c.level, '/', c.group_number, ' เลขที่ ', s.student_code) as class_name,
                    d.department_name,
                    sar.total_attendance_days,
                    sar.total_absence_days,
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END as attendance_rate
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  JOIN classes c ON s.current_class_id = c.class_id
                  JOIN departments d ON c.department_id = d.department_id
                  LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
                  WHERE s.student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo json_encode(['error' => 'Student not found']);
            return;
        }
        
        // Format attendance rate
        $student['attendance_rate'] = round($student['attendance_rate'], 1);
        
        // Get attendance history (last 10 days)
        $query = "SELECT 
                    a.date,
                    a.attendance_status,
                    a.check_time,
                    a.remarks
                  FROM attendance a
                  WHERE a.student_id = ? AND a.academic_year_id = ?
                  ORDER BY a.date DESC
                  LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId, $academicYearId]);
        $attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format attendance history
        $formattedHistory = [];
        foreach ($attendanceHistory as $record) {
            // Convert date format
            $date = new DateTime($record['date']);
            $formattedDate = $date->format('d/m/Y');
            
            // Determine status and class
            $status = '';
            $statusClass = '';
            switch ($record['attendance_status']) {
                case 'present':
                    $status = 'มา';
                    $statusClass = 'success';
                    break;
                case 'absent':
                    $status = 'ขาด';
                    $statusClass = 'danger';
                    break;
                case 'late':
                    $status = 'มาสาย';
                    $statusClass = 'warning';
                    break;
                case 'leave':
                    $status = 'ลา';
                    $statusClass = 'info';
                    break;
            }
            
            // Format time
            $time = $record['check_time'] ? date('H:i', strtotime($record['check_time'])) : '-';
            
            $formattedHistory[] = [
                'date' => $formattedDate,
                'status' => $status,
                'statusClass' => $statusClass,
                'time' => $time,
                'remark' => $record['remarks'] ?: '-'
            ];
        }
        
        // Get notification history
        // In a real system, this would query a notifications table
        // Using example data for now
        $notificationHistory = [
            ['date' => '16/03/2568', 'type' => 'แจ้งเตือนความเสี่ยง', 'sender' => 'จารุวรรณ บุญมี', 'status' => 'ส่งสำเร็จ', 'statusClass' => 'success'],
            ['date' => '01/03/2568', 'type' => 'แจ้งเตือนปกติ', 'sender' => 'อ.ประสิทธิ์ ดีเลิศ', 'status' => 'ส่งสำเร็จ', 'statusClass' => 'success'],
            ['date' => '15/02/2568', 'type' => 'แจ้งเตือนปกติ', 'sender' => 'อ.ประสิทธิ์ ดีเลิศ', 'status' => 'ส่งสำเร็จ', 'statusClass' => 'success']
        ];
        
        // Get monthly attendance trends (last 3 months)
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        $monthlyData = [];
        for ($i = 0; $i < 3; $i++) {
            $month = $currentMonth - $i;
            $year = $currentYear;
            
            if ($month <= 0) {
                $month += 12;
                $year--;
            }
            
            // Get attendance data for this month
            $query = "SELECT 
                        COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) as present_days,
                        COUNT(*) as total_days
                      FROM attendance
                      WHERE student_id = ? AND academic_year_id = ? 
                      AND MONTH(date) = ? AND YEAR(date) = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$studentId, $academicYearId, $month, $year]);
            $monthData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $rate = 0;
            if ($monthData['total_days'] > 0) {
                $rate = ($monthData['present_days'] / $monthData['total_days']) * 100;
            }
            
            $monthlyData[] = [
                'month' => getThaiMonth($month),
                'rate' => round($rate, 1)
            ];
        }
        
        // Reverse array to get chronological order
        $monthlyData = array_reverse($monthlyData);
        
        // Prepare the full response
        $response = [
            'success' => true,
            'data' => [
                'student' => $student,
                'attendanceHistory' => $formattedHistory,
                'notificationHistory' => $notificationHistory,
                'monthlyTrend' => [
                    'labels' => array_column($monthlyData, 'month'),
                    'rates' => array_column($monthlyData, 'rate')
                ]
            ]
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Generate filtered report based on criteria
 */
function generateReport($data) {
    global $conn;
    
    try {
        // Process and validate filters
        $reportType = $data['reportType'] ?? 'monthly';
        $departmentId = $data['department'] ?? '';
        $period = $data['period'] ?? 'current';
        $classLevel = $data['classLevel'] ?? '';
        $classRoom = $data['classRoom'] ?? '';
        $startDate = $data['startDate'] ?? '';
        $endDate = $data['endDate'] ?? '';
        $studentSearch = $data['studentSearch'] ?? '';
        
        // Set response based on report type
        $response = [
            'success' => true,
            'message' => 'สร้างรายงานเรียบร้อยแล้ว',
            'reportType' => $reportType,
            'data' => []
        ];
        
        // Fetch different data based on report type
        switch ($reportType) {
            case 'daily':
                // Daily report logic
                $response['data'] = getDailyReportData($period, $startDate, $endDate, $departmentId, $classLevel, $classRoom);
                break;
                
            case 'weekly':
                // Weekly report logic
                $response['data'] = getWeeklyReportData($period, $startDate, $endDate, $departmentId, $classLevel, $classRoom);
                break;
                
            case 'monthly':
                // Monthly report logic
                $response['data'] = getMonthlyReportData($period, $startDate, $endDate, $departmentId, $classLevel, $classRoom);
                break;
                
            case 'semester':
                // Semester report logic
                $response['data'] = getSemesterReportData($departmentId, $classLevel, $classRoom);
                break;
                
            case 'class':
                // Class report logic
                $response['data'] = getClassReportData($departmentId, $classLevel, $classRoom);
                break;
                
            case 'student':
                // Student report logic
                $response['data'] = getStudentReportData($studentSearch);
                break;
                
            default:
                $response = [
                    'success' => false,
                    'error' => 'Invalid report type'
                ];
                break;
        }
        
        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get data for daily report
 */
function getDailyReportData($period, $startDate, $endDate, $departmentId, $classLevel, $classRoom) {
    global $conn;
    
    // Logic for fetching daily report data
    // This is a placeholder and should be expanded based on specific requirements
    
    return [
        'summary' => [
            'date' => date('d/m/Y'),
            'total_students' => 1250,
            'present_students' => 1175,
            'absent_students' => 75,
            'attendance_rate' => 94.0
        ],
        'report_date' => date('d/m/Y')
    ];
}

/**
 * Get data for weekly report
 */
function getWeeklyReportData($period, $startDate, $endDate, $departmentId, $classLevel, $classRoom) {
    global $conn;
    
    // Logic for fetching weekly report data
    // This is a placeholder and should be expanded based on specific requirements
    
    return [
        'summary' => [
            'week' => 'สัปดาห์ที่ 12 (12-18 มีนาคม 2568)',
            'total_students' => 1250,
            'avg_attendance_rate' => 92.5,
            'trend' => [
                ['day' => 'จันทร์', 'rate' => 94.2],
                ['day' => 'อังคาร', 'rate' => 93.8],
                ['day' => 'พุธ', 'rate' => 92.1],
                ['day' => 'พฤหัสบดี', 'rate' => 91.7],
                ['day' => 'ศุกร์', 'rate' => 90.8]
            ]
        ],
        'report_period' => 'สัปดาห์ที่ 12 (12-18 มีนาคม 2568)'
    ];
}

/**
 * Get data for monthly report
 */
function getMonthlyReportData($period, $startDate, $endDate, $departmentId, $classLevel, $classRoom) {
    global $conn;
    
    // Logic for fetching monthly report data
    // This is a placeholder and should be expanded based on specific requirements
    
    return [
        'summary' => [
            'month' => 'มีนาคม 2568',
            'total_students' => 1250,
            'avg_attendance_rate' => 92.7,
            'risk_students' => 35,
            'attendance_days' => 22
        ],
        'report_period' => 'มีนาคม 2568'
    ];
}

/**
 * Get data for semester report
 */
function getSemesterReportData($departmentId, $classLevel, $classRoom) {
    global $conn;
    
    // Logic for fetching semester report data
    // This is a placeholder and should be expanded based on specific requirements
    
    return [
        'summary' => [
            'semester' => 'ภาคเรียนที่ 2/2568',
            'total_students' => 1250,
            'avg_attendance_rate' => 91.5,
            'failed_students' => 28,
            'at_risk_students' => 42,
            'attendance_days' => 98
        ],
        'report_period' => 'ภาคเรียนที่ 2/2568'
    ];
}

/**
 * Get data for class report
 */
function getClassReportData($departmentId, $classLevel, $classRoom) {
    global $conn;
    
    // Logic for fetching class report data
    // This is a placeholder and should be expanded based on specific requirements
    
    return [
        'summary' => [
            'class' => 'ม.5/1',
            'department' => 'วิทยาศาสตร์-คณิตศาสตร์',
            'total_students' => 42,
            'avg_attendance_rate' => 94.3,
            'risk_students' => 2
        ],
        'report_class' => 'ม.5/1'
    ];
}

/**
 * Get data for student report
 */
function getStudentReportData($studentSearch) {
    global $conn;
    
    // Logic for fetching student report data
    // This is a placeholder and should be expanded based on specific requirements
    
    return [
        'student' => [
            'id' => 123,
            'name' => 'นายธนกฤต สุขใจ',
            'code' => '16478',
            'class' => 'ม.6/2',
            'attendance_rate' => 68.5,
            'present_days' => 15,
            'absent_days' => 7,
            'status' => 'ตกกิจกรรม'
        ],
        'report_student' => 'นายธนกฤต สุขใจ'
    ];
}

/**
 * Export report data to specified format
 */
function exportReport($data) {
    // Process export request
    $format = $data['format'] ?? 'xlsx';
    $reportId = $data['reportId'] ?? null;
    
    // Check if we have valid report data
    if (!$reportId) {
        echo json_encode(['error' => 'Invalid report ID']);
        return;
    }
    
    // Generate a download URL
    $downloadUrl = "api/download_report.php?id={$reportId}&format={$format}";
    
    echo json_encode([
        'success' => true,
        'message' => 'กำลังเตรียมไฟล์สำหรับดาวน์โหลด',
        'download_url' => $downloadUrl
    ]);
}

/**
 * Send notification to parents of at-risk students
 */
function sendNotification($data) {
    // Process notification request
    $studentIds = $data['studentIds'] ?? [];
    $templateId = $data['templateId'] ?? null;
    $message = $data['message'] ?? '';
    
    // Validate inputs
    if (empty($studentIds)) {
        echo json_encode(['error' => 'No students selected']);
        return;
    }
    
    if (!$templateId && empty($message)) {
        echo json_encode(['error' => 'Either template or custom message is required']);
        return;
    }
    
    // In a real system, this would send notifications via LINE OA
    // For now, just return success
    
    echo json_encode([
        'success' => true,
        'message' => 'ส่งข้อความแจ้งเตือนสำเร็จ',
        'sent_to' => count($studentIds),
        'notification_id' => rand(1000, 9999)
    ]);
}

/**
 * Helper function to convert month number to Thai month name
 */
function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}
?>
<?php
/**
 * dashboard_api.php - API สำหรับหน้าแดชบอร์ด
 * ระบบ STUDENT-Prasat
 */

// กำหนด headers สำหรับป้องกัน caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// กำหนด header เป็น JSON
header('Content-Type: application/json; charset=UTF-8');

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบการร้องขอ
if (isset($_GET['action'])) {
    // ร้องขอแบบ GET
    handleGetRequest();
} elseif (isset($_POST['action'])) {
    // ร้องขอแบบ POST
    handlePostRequest();
} else {
    // ไม่ระบุ action
    echo json_encode([
        'success' => false,
        'message' => 'ไม่ระบุการกระทำ (action)'
    ]);
}

/**
 * จัดการคำขอแบบ GET
 */
function handleGetRequest() {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get_summary':
            getSummaryData();
            break;
            
        case 'get_attendance_stats':
            getAttendanceStats();
            break;
            
        case 'get_risk_students':
            getRiskStudents();
            break;
            
        case 'get_recent_activities':
            getRecentActivities();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่รู้จักการกระทำ: ' . $action
            ]);
            break;
    }
}

/**
 * จัดการคำขอแบบ POST
 */
function handlePostRequest() {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'notify_risk_students':
            notifyRiskStudents();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่รู้จักการกระทำ: ' . $action
            ]);
            break;
    }
}

/**
 * ดึงข้อมูลสรุปสำหรับแดชบอร์ด
 */
function getSummaryData() {
    try {
        $conn = getDB();
        
        // ดึงจำนวนนักเรียนทั้งหมด
        $query = "SELECT 
                (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students,
                (SELECT COUNT(*) FROM teachers) as total_teachers,
                (SELECT COUNT(*) FROM classes WHERE is_active = 1) as total_classes,
                (SELECT COUNT(*) FROM risk_students WHERE risk_level IN ('high', 'critical')) as risk_students";
        
        $stmt = $conn->query($query);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลการเข้าแถววันนี้
        $today = date('Y-m-d');
        $todayQuery = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present,
                      SUM(CASE WHEN is_present = 0 THEN 1 ELSE 0 END) as absent
                      FROM attendance
                      WHERE date = ?";
        
        $todayStmt = $conn->prepare($todayQuery);
        $todayStmt->execute([$today]);
        $todayAttendance = $todayStmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลการเข้าแถวสัปดาห์นี้
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        
        $weekQuery = "SELECT 
                     COUNT(*) as total,
                     SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present,
                     SUM(CASE WHEN is_present = 0 THEN 1 ELSE 0 END) as absent
                     FROM attendance
                     WHERE date BETWEEN ? AND ?";
        
        $weekStmt = $conn->prepare($weekQuery);
        $weekStmt->execute([$weekStart, $weekEnd]);
        $weekAttendance = $weekStmt->fetch(PDO::FETCH_ASSOC);
        
        // รวมผลลัพธ์
        $result = [
            'success' => true,
            'summary' => $summary,
            'today_attendance' => $todayAttendance,
            'week_attendance' => $weekAttendance
        ];
        
        echo json_encode($result);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสรุป: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลสถิติการเข้าแถว
 */
function getAttendanceStats() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลการเข้าแถวรายวันในเดือนนี้
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        $dailyQuery = "SELECT 
                      date,
                      COUNT(*) as total,
                      SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present,
                      SUM(CASE WHEN is_present = 0 THEN 1 ELSE 0 END) as absent
                      FROM attendance
                      WHERE date BETWEEN ? AND ?
                      GROUP BY date
                      ORDER BY date";
        
        $dailyStmt = $conn->prepare($dailyQuery);
        $dailyStmt->execute([$monthStart, $monthEnd]);
        $dailyStats = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลการเข้าแถวรายเดือน
        $monthlyQuery = "SELECT 
                        YEAR(date) as year,
                        MONTH(date) as month,
                        COUNT(*) as total,
                        SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN is_present = 0 THEN 1 ELSE 0 END) as absent
                        FROM attendance
                        GROUP BY YEAR(date), MONTH(date)
                        ORDER BY YEAR(date), MONTH(date)";
        
        $monthlyStmt = $conn->query($monthlyQuery);
        $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงข้อมูลให้เหมาะกับการแสดงในกราฟ
        $dailyChartData = [];
        foreach ($dailyStats as $day) {
            $date = date('d', strtotime($day['date']));
            $rate = ($day['total'] > 0) ? ($day['present'] / $day['total'] * 100) : 0;
            
            $dailyChartData[] = [
                'date' => $date,
                'present' => intval($day['present']),
                'absent' => intval($day['absent']),
                'rate' => round($rate, 2)
            ];
        }
        
        $monthlyChartData = [];
        foreach ($monthlyStats as $month) {
            $monthName = date('M', mktime(0, 0, 0, $month['month'], 1, $month['year']));
            $rate = ($month['total'] > 0) ? ($month['present'] / $month['total'] * 100) : 0;
            
            $monthlyChartData[] = [
                'month' => $monthName,
                'present' => intval($month['present']),
                'absent' => intval($month['absent']),
                'rate' => round($rate, 2)
            ];
        }
        
        // รวมผลลัพธ์
        $result = [
            'success' => true,
            'daily' => $dailyChartData,
            'monthly' => $monthlyChartData
        ];
        
        echo json_encode($result);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติการเข้าแถว: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
 */
function getRiskStudents() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
        $query = "SELECT s.student_id, s.student_code, u.title, u.first_name, u.last_name,
                 c.level, c.group_number, d.department_name,
                 rs.risk_level, rs.absence_count, rs.notification_sent,
                 (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name)
                  FROM class_advisors ca
                  JOIN teachers t ON ca.teacher_id = t.teacher_id
                  WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                  LIMIT 1) as advisor_name
                 FROM risk_students rs
                 JOIN students s ON rs.student_id = s.student_id
                 JOIN users u ON s.user_id = u.user_id
                 JOIN classes c ON s.current_class_id = c.class_id
                 JOIN departments d ON c.department_id = d.department_id
                 WHERE rs.risk_level IN ('high', 'critical')
                 AND s.status = 'กำลังศึกษา'
                 ORDER BY rs.risk_level DESC, rs.absence_count DESC
                 LIMIT 10";
        
        $stmt = $conn->query($query);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงระดับความเสี่ยงเป็นภาษาไทย
        foreach ($students as &$student) {
            switch ($student['risk_level']) {
                case 'low':
                    $student['risk_level_th'] = 'ต่ำ';
                    break;
                case 'medium':
                    $student['risk_level_th'] = 'ปานกลาง';
                    break;
                case 'high':
                    $student['risk_level_th'] = 'สูง';
                    break;
                case 'critical':
                    $student['risk_level_th'] = 'วิกฤต';
                    break;
                default:
                    $student['risk_level_th'] = 'ไม่ระบุ';
            }
            
            // เพิ่มคลาสสำหรับการแสดงสีตามระดับความเสี่ยง
            switch ($student['risk_level']) {
                case 'low':
                    $student['risk_class'] = 'success';
                    break;
                case 'medium':
                    $student['risk_class'] = 'warning';
                    break;
                case 'high':
                    $student['risk_class'] = 'danger';
                    break;
                case 'critical':
                    $student['risk_class'] = 'critical';
                    break;
                default:
                    $student['risk_class'] = '';
            }
        }
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลกิจกรรมล่าสุด
 */
function getRecentActivities() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลการเข้าแถวล่าสุด
        $query = "SELECT a.attendance_id, a.date, a.check_time, a.is_present, 
                 s.student_code, u.title, u.first_name, u.last_name,
                 c.level, c.group_number, d.department_name,
                 cu.first_name as checker_first_name, cu.last_name as checker_last_name
                 FROM attendance a
                 JOIN students s ON a.student_id = s.student_id
                 JOIN users u ON s.user_id = u.user_id
                 LEFT JOIN classes c ON s.current_class_id = c.class_id
                 LEFT JOIN departments d ON c.department_id = d.department_id
                 LEFT JOIN users cu ON a.checker_user_id = cu.user_id
                 ORDER BY a.created_at DESC
                 LIMIT 10";
        
        $stmt = $conn->query($query);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงข้อมูลให้อ่านง่ายขึ้น
        foreach ($activities as &$activity) {
            $activity['date_formatted'] = date('d/m/Y', strtotime($activity['date']));
            $activity['time_formatted'] = date('H:i', strtotime($activity['check_time']));
            $activity['student_name'] = $activity['title'] . $activity['first_name'] . ' ' . $activity['last_name'];
            $activity['class_name'] = $activity['level'] . '/' . $activity['group_number'] . ' ' . $activity['department_name'];
            $activity['checker_name'] = $activity['checker_first_name'] ? $activity['checker_first_name'] . ' ' . $activity['checker_last_name'] : 'ระบบ';
            $activity['status'] = $activity['is_present'] ? 'เข้าแถว' : 'ขาด';
            $activity['status_class'] = $activity['is_present'] ? 'success' : 'danger';
        }
        
        echo json_encode([
            'success' => true,
            'activities' => $activities
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลกิจกรรมล่าสุด: ' . $e->getMessage()
        ]);
    }
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรม
 */
function notifyRiskStudents() {
    // เรียกใช้งานฟังก์ชันจากไฟล์ students_api.php
    include_once 'students_api.php';
    
    if (function_exists('notifyRiskStudentParents')) {
        notifyRiskStudentParents();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบฟังก์ชันสำหรับการแจ้งเตือนผู้ปกครอง'
        ]);
    }
}
<?php
/**
 * api/notifications/index.php - API endpoint for sending notifications
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 * 
 * This API handles sending notifications to students/parents.
 */

// Set response content type to JSON
header('Content-Type: application/json');

// Include necessary files
require_once '../../db_connect.php';

// Check user session and permissions
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
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
    if (preg_match('/\/api\/notifications\/(.+)/', $requestUri, $matches)) {
        $action = $matches[1];
    }
    
    // Only allow POST requests
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!$postData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }
    
    // Process based on action
    switch ($action) {
        case 'individual':
            sendIndividualNotification($db, $postData);
            break;
        case 'bulk':
            sendBulkNotification($db, $postData);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Send individual notification to a student/parent
 * 
 * @param PDO $db Database connection
 * @param array $data Request data
 */
function sendIndividualNotification($db, $data) {
    // Validate required fields
    if (!isset($data['student_id']) || !isset($data['message']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $studentId = $data['student_id'];
    $message = $data['message'];
    $userId = $_SESSION['user_id'];
    
    // Verify student exists
    $stmt = $db->prepare("SELECT student_id FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
        return;
    }
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // 1. Save notification in database
        $stmt = $db->prepare("
            INSERT INTO notifications 
            (user_id, related_student_id, type, title, notification_message, created_at) 
            VALUES 
            (:user_id, :student_id, :type, :title, :message, NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':student_id' => $studentId,
            ':type' => 'risk_alert',
            ':title' => 'แจ้งเตือนความเสี่ยงตกกิจกรรม',
            ':message' => $message
        ]);
        
        $notificationId = $db->lastInsertId();
        
        // 2. Try to send LINE notification to parent
        $success = sendLineNotification($db, $studentId, $message);
        
        // 3. Update sent status in line_notifications table
        $stmt = $db->prepare("
            INSERT INTO line_notifications 
            (user_id, message, sent_at, status, notification_type) 
            VALUES 
            (:user_id, :message, NOW(), :status, 'risk_alert')
        ");
        $stmt->execute([
            ':user_id' => getUserIdForStudent($db, $studentId),
            ':message' => $message,
            ':status' => $success ? 'sent' : 'pending'
        ]);
        
        // Commit transaction
        $db->commit();
        
        // Return success response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'notification_id' => $notificationId,
            'line_sent' => $success
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        throw $e;
    }
}

/**
 * Send bulk notifications to multiple students/parents
 * 
 * @param PDO $db Database connection
 * @param array $data Request data
 */
function sendBulkNotification($db, $data) {
    // Validate required fields
    if (!isset($data['message']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required message']);
        return;
    }
    
    $message = $data['message'];
    $filters = isset($data['filters']) ? $data['filters'] : [];
    $userId = $_SESSION['user_id'];
    
    // Build query to get at-risk students based on filters
    $sql = "
        SELECT s.student_id
        FROM students s
        JOIN student_academic_records sar ON s.student_id = sar.student_id
        JOIN classes c ON s.current_class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
        LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
        WHERE ay.is_active = 1
        AND s.status = 'กำลังศึกษา'
        AND (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80
    ";
    
    $params = [];
    
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
    
    // Prepare and execute statement
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error' => 'No students found matching the criteria',
            'sent_count' => 0
        ]);
        return;
    }
    
    // Send notifications
    $sentCount = 0;
    $failedCount = 0;
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        foreach ($students as $student) {
            $studentId = $student['student_id'];
            
            // 1. Save notification in database
            $stmt = $db->prepare("
                INSERT INTO notifications 
                (user_id, related_student_id, type, title, notification_message, created_at) 
                VALUES 
                (:user_id, :student_id, :type, :title, :message, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':student_id' => $studentId,
                ':type' => 'risk_alert',
                ':title' => 'แจ้งเตือนความเสี่ยงตกกิจกรรม',
                ':message' => $message
            ]);
            
            // 2. Try to send LINE notification to parent
            $success = sendLineNotification($db, $studentId, $message);
            
            // 3. Update sent status in line_notifications table
            $stmt = $db->prepare("
                INSERT INTO line_notifications 
                (user_id, message, sent_at, status, notification_type) 
                VALUES 
                (:user_id, :message, NOW(), :status, 'risk_alert')
            ");
            $stmt->execute([
                ':user_id' => getUserIdForStudent($db, $studentId),
                ':message' => $message,
                ':status' => $success ? 'sent' : 'pending'
            ]);
            
            if ($success) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }
        
        // 4. Log bulk notification action
        $stmt = $db->prepare("
            INSERT INTO admin_actions 
            (admin_id, action_type, action_details, action_date) 
            VALUES 
            (:admin_id, :action_type, :action_details, NOW())
        ");
        $stmt->execute([
            ':admin_id' => $userId,
            ':action_type' => 'record_activity_attendance',
            ':action_details' => "ส่งการแจ้งเตือนกลุ่มให้กับนักเรียนเสี่ยงตกกิจกรรม จำนวน " . count($students) . " คน"
        ]);
        
        // Commit transaction
        $db->commit();
        
        // Return success response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'total_count' => count($students)
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        throw $e;
    }
}

/**
 * Send LINE notification to a parent
 * 
 * @param PDO $db Database connection
 * @param int $studentId Student ID
 * @param string $message Message to send
 * @return bool Whether notification was sent successfully
 */
function sendLineNotification($db, $studentId, $message) {
    // In a real implementation, this would use the LINE API to send notifications
    // For now, we'll simulate success with a 90% chance
    return (rand(1, 100) <= 90);
}

/**
 * Get user ID for a student
 * 
 * @param PDO $db Database connection
 * @param int $studentId Student ID
 * @return int User ID
 */
function getUserIdForStudent($db, $studentId) {
    $stmt = $db->prepare("SELECT user_id FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return null;
    }
    
    return $student['user_id'];
}
?>
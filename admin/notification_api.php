<?php
/**
 * notification_api.php - ไฟล์สำหรับการส่งข้อความผ่าน LINE API
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI ดูแลผู้เรียน (STUDENT-Prasat)
 */

class LineNotificationAPI {
    private $channelAccessToken;
    private $apiEndpoint = 'https://api.line.me/v2/bot/message/push';
    private $conn;

    /**
     * สร้าง LineNotificationAPI object ใหม่
     * 
     * @param string $channelAccessToken LINE Channel Access Token
     * @param PDO $conn เชื่อมต่อฐานข้อมูล
     */
    public function __construct($channelAccessToken, $conn) {
        $this->channelAccessToken = $channelAccessToken;
        $this->conn = $conn;
    }

    /**
     * ส่งข้อความไปยังผู้ใช้
     * 
     * @param string $lineId LINE User ID ของผู้รับ
     * @param array $messages ข้อความที่ต้องการส่ง
     * @return array ผลลัพธ์การส่ง
     */
    public function sendMessage($lineId, $messages) {
        if (empty($lineId) || empty($messages) || empty($this->channelAccessToken)) {
            return [
                'success' => false,
                'message' => 'ข้อมูลไม่ครบถ้วน กรุณาตรวจสอบ LINE ID, ข้อความ และ Access Token'
            ];
        }

        // เตรียมข้อมูลสำหรับส่ง
        $data = [
            'to' => $lineId,
            'messages' => $messages
        ];

        // กำหนด HTTP headers
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->channelAccessToken
        ];

        // ตั้งค่า cURL
        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // ทำการส่งคำขอ
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // ตรวจสอบผลลัพธ์
        if ($httpCode === 200) {
            // บันทึกประวัติการส่ง
            $this->logNotification($lineId, $messages);
            
            return [
                'success' => true,
                'message' => 'ส่งข้อความสำเร็จ',
                'response' => $response
            ];
        } else {
            // บันทึกการส่งที่ล้มเหลว
            $this->logNotification($lineId, $messages, 'failed', $response);
            
            return [
                'success' => false,
                'message' => 'ไม่สามารถส่งข้อความได้',
                'http_code' => $httpCode,
                'error' => $error,
                'response' => $response
            ];
        }
    }

    /**
     * สร้างข้อความ text message
     * 
     * @param string $text ข้อความที่ต้องการส่ง
     * @return array ข้อมูลข้อความในรูปแบบ LINE API
     */
    public function createTextMessage($text) {
        return [
            'type' => 'text',
            'text' => $text
        ];
    }

    /**
     * สร้างข้อความรูปภาพ
     * 
     * @param string $originalContentUrl URL ของรูปภาพขนาดเต็ม
     * @param string $previewImageUrl URL ของรูปภาพตัวอย่าง
     * @return array ข้อมูลรูปภาพในรูปแบบ LINE API
     */
    public function createImageMessage($originalContentUrl, $previewImageUrl = null) {
        if ($previewImageUrl === null) {
            $previewImageUrl = $originalContentUrl;
        }

        return [
            'type' => 'image',
            'originalContentUrl' => $originalContentUrl,
            'previewImageUrl' => $previewImageUrl
        ];
    }

    /**
     * สร้างข้อความปุ่ม
     * 
     * @param string $text ข้อความที่ต้องการแสดง
     * @param string $label ข้อความบนปุ่ม
     * @param string $url URL ที่ต้องการเปิด
     * @return array ข้อมูลข้อความปุ่มในรูปแบบ LINE API
     */
    public function createButtonMessage($text, $label, $url) {
        return [
            'type' => 'template',
            'altText' => $text,
            'template' => [
                'type' => 'buttons',
                'text' => $text,
                'actions' => [
                    [
                        'type' => 'uri',
                        'label' => $label,
                        'uri' => $url
                    ]
                ]
            ]
        ];
    }

    /**
     * สร้างรูปภาพกราฟจากข้อมูลการเข้าแถว
     * 
     * @param int $studentId รหัสนักเรียน
     * @param string $startDate วันที่เริ่มต้น
     * @param string $endDate วันที่สิ้นสุด
     * @return string URL ของรูปภาพกราฟ
     */
    public function generateAttendanceChart($studentId, $startDate, $endDate) {
        // ดึงข้อมูลการเข้าแถวตามช่วงวันที่
        $attendanceData = $this->getAttendanceData($studentId, $startDate, $endDate);
        
        // สร้าง URL สำหรับเรียกใช้ chart_generator.php
        $chartUrl = 'https://your-domain.com/chart_generator.php';
        $params = [
            'student_id' => $studentId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'token' => md5($studentId . $startDate . $endDate . 'YOUR_SECRET_KEY')
        ];
        
        $chartUrl .= '?' . http_build_query($params);
        
        return $chartUrl;
    }

    /**
     * ดึงข้อมูลการเข้าแถวของนักเรียน
     * 
     * @param int $studentId รหัสนักเรียน
     * @param string $startDate วันที่เริ่มต้น
     * @param string $endDate วันที่สิ้นสุด
     * @return array ข้อมูลการเข้าแถว
     */
    private function getAttendanceData($studentId, $startDate, $endDate) {
        try {
            $query = "
                SELECT 
                    a.date,
                    a.attendance_status,
                    a.check_method,
                    a.check_time
                FROM 
                    attendance a
                    JOIN academic_years ay ON a.academic_year_id = ay.academic_year_id
                WHERE 
                    a.student_id = ? 
                    AND a.date BETWEEN ? AND ?
                    AND ay.is_active = 1
                ORDER BY 
                    a.date ASC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$studentId, $startDate, $endDate]);
            $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // นับจำนวนวันตามสถานะ
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $leaveCount = 0;
            
            $dates = [];
            $statuses = [];
            
            foreach ($attendanceRecords as $record) {
                $dates[] = date('d/m', strtotime($record['date']));
                $statuses[] = $record['attendance_status'];
                
                switch ($record['attendance_status']) {
                    case 'present':
                        $presentCount++;
                        break;
                    case 'absent':
                        $absentCount++;
                        break;
                    case 'late':
                        $lateCount++;
                        break;
                    case 'leave':
                        $leaveCount++;
                        break;
                }
            }
            
            $totalDays = count($attendanceRecords);
            $attendanceRate = ($totalDays > 0) ? round(($presentCount / $totalDays) * 100, 2) : 0;
            
            return [
                'records' => $attendanceRecords,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'leave_count' => $leaveCount,
                'total_days' => $totalDays,
                'attendance_rate' => $attendanceRate,
                'dates' => $dates,
                'statuses' => $statuses
            ];
        } catch (PDOException $e) {
            error_log("Error getting attendance data: " . $e->getMessage());
            return [
                'records' => [],
                'present_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
                'leave_count' => 0,
                'total_days' => 0,
                'attendance_rate' => 0,
                'dates' => [],
                'statuses' => []
            ];
        }
    }

    /**
     * บันทึกประวัติการส่งข้อความ
     * 
     * @param string $lineId LINE User ID ของผู้รับ
     * @param array $messages ข้อความที่ส่ง
     * @param string $status สถานะการส่ง (sent, failed)
     * @param string $errorMessage ข้อความแสดงข้อผิดพลาด (ถ้ามี)
     * @return bool สถานะการบันทึก
     */
    private function logNotification($lineId, $messages, $status = 'sent', $errorMessage = null) {
        try {
            // หา user_id จาก line_id
            $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE line_id = ?");
            $stmt->execute([$lineId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                error_log("Cannot find user with LINE ID: $lineId");
                return false;
            }
            
            // แปลงข้อความเป็น JSON string
            $messageText = '';
            foreach ($messages as $msg) {
                if ($msg['type'] === 'text') {
                    $messageText = $msg['text'];
                    break;
                }
            }
            
            // บันทึกลงฐานข้อมูล
            $stmt = $this->conn->prepare("
                INSERT INTO line_notifications (
                    user_id, message, sent_at, status, error_message, notification_type
                ) VALUES (
                    ?, ?, NOW(), ?, ?, 'attendance'
                )
            ");
            
            $stmt->execute([
                $user['user_id'],
                $messageText,
                $status,
                $errorMessage
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error logging notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึงข้อมูล LINE ID ของผู้ปกครองนักเรียน
     * 
     * @param int $studentId รหัสนักเรียน
     * @return array รายการ LINE ID ของผู้ปกครอง
     */
    public function getParentLineIds($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.line_id
                FROM parent_student_relation psr
                JOIN parents p ON psr.parent_id = p.parent_id
                JOIN users u ON p.user_id = u.user_id
                WHERE psr.student_id = ? AND u.line_id IS NOT NULL
            ");
            
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting parent LINE IDs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงข้อมูลนักเรียน
     * 
     * @param int $studentId รหัสนักเรียน
     * @return array ข้อมูลนักเรียน
     */
    public function getStudentInfo($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    s.student_id, s.student_code, s.title, 
                    u.first_name, u.last_name,
                    c.level, c.group_number,
                    d.department_name,
                    (
                        SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name)
                        FROM class_advisors ca
                        JOIN teachers t ON ca.teacher_id = t.teacher_id
                        WHERE ca.class_id = s.current_class_id AND ca.is_primary = 1
                        LIMIT 1
                    ) as advisor_name,
                    (
                        SELECT u2.phone_number
                        FROM class_advisors ca
                        JOIN teachers t ON ca.teacher_id = t.teacher_id
                        JOIN users u2 ON t.user_id = u2.user_id
                        WHERE ca.class_id = s.current_class_id AND ca.is_primary = 1
                        LIMIT 1
                    ) as advisor_phone
                FROM 
                    students s
                    JOIN users u ON s.user_id = u.user_id
                    LEFT JOIN classes c ON s.current_class_id = c.class_id
                    LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE 
                    s.student_id = ?
            ");
            
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                $student['full_name'] = $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'];
                $student['class_name'] = $student['level'] . '/' . $student['group_number'];
            }
            
            return $student;
        } catch (PDOException $e) {
            error_log("Error getting student info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * สร้าง URL สำหรับดูรายละเอียดการเข้าแถว
     * 
     * @param int $studentId รหัสนักเรียน
     * @param string $startDate วันที่เริ่มต้น
     * @param string $endDate วันที่สิ้นสุด
     * @return string URL
     */
    public function generateDetailUrl($studentId, $startDate, $endDate) {
        // สร้าง URL สำหรับเรียกดูรายละเอียด
        $token = md5($studentId . $startDate . $endDate . 'YOUR_SECRET_KEY');
        $detailUrl = 'https://your-domain.com/attendance_detail.php';
        $params = [
            'student_id' => $studentId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'token' => $token
        ];
        
        return $detailUrl . '?' . http_build_query($params);
    }
    
    /**
     * แทนที่ตัวแปรในข้อความ
     * 
     * @param string $message ข้อความเทมเพลต
     * @param array $data ข้อมูลสำหรับแทนที่
     * @return string ข้อความที่แทนที่ตัวแปรแล้ว
     */
    public function replaceTemplateVariables($message, $data) {
        $patterns = [
            '/{{ชื่อนักเรียน}}/' => $data['student_name'] ?? '',
            '/{{ชั้นเรียน}}/' => $data['class'] ?? '',
            '/{{จำนวนวันเข้าแถว}}/' => $data['attendance_days'] ?? '0',
            '/{{จำนวนวันทั้งหมด}}/' => $data['total_days'] ?? '0',
            '/{{ร้อยละการเข้าแถว}}/' => $data['attendance_rate'] ?? '0',
            '/{{จำนวนวันขาด}}/' => $data['absence_days'] ?? '0',
            '/{{สถานะการเข้าแถว}}/' => $data['attendance_status'] ?? '',
            '/{{ชื่อครูที่ปรึกษา}}/' => $data['advisor_name'] ?? '',
            '/{{เบอร์โทรครู}}/' => $data['advisor_phone'] ?? ''
        ];
        
        return preg_replace(array_keys($patterns), array_values($patterns), $message);
    }
    
    /**
     * คำนวณค่าใช้จ่ายในการส่งข้อความ
     * 
     * @param bool $includeText รวมข้อความหรือไม่
     * @param bool $includeChart รวมกราฟหรือไม่
     * @param bool $includeLink รวมลิงก์หรือไม่
     * @return float ค่าใช้จ่ายรวม
     */
    public function calculateMessageCost($includeText = true, $includeChart = false, $includeLink = false) {
        $textCost = $includeText ? 0.075 : 0; // บาทต่อข้อความ
        $chartCost = $includeChart ? 0.15 : 0; // บาทต่อรูปภาพ
        $linkCost = $includeLink ? 0.075 : 0; // บาทต่อลิงก์
        
        return $textCost + $chartCost + $linkCost;
    }
}
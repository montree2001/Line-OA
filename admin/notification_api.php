<?php
/**
 * notification_api.php - API สำหรับการส่งข้อความแจ้งเตือนผ่าน LINE
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI ดูแลผู้เรียน
 */

class LineNotificationAPI {
    private $accessToken;
    private $conn;
    private $messageEndpoint = 'https://api.line.me/v2/bot/message/push';
    private $multicastEndpoint = 'https://api.line.me/v2/bot/message/multicast';
    
    /**
     * สร้าง instance ของ API
     * 
     * @param string $accessToken LINE Channel Access Token
     * @param PDO $conn การเชื่อมต่อฐานข้อมูล
     */
    public function __construct($accessToken, $conn) {
        $this->accessToken = $accessToken;
        $this->conn = $conn;
    }
    
    /**
     * ส่งข้อความไปยังผู้ใช้คนเดียว
     * 
     * @param string $lineId LINE User ID ของผู้รับข้อความ
     * @param array $messages ข้อความที่ต้องการส่ง (ในรูปแบบของ LINE Messaging API)
     * @return array ผลลัพธ์การส่ง
     */
    public function sendMessage($lineId, $messages) {
        // ตรวจสอบว่ามี LINE ID หรือไม่
        if (empty($lineId)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ LINE ID ของผู้รับ',
                'error_code' => 'NO_LINE_ID'
            ];
        }
        
        // ตรวจสอบว่ามีข้อความหรือไม่
        if (empty($messages)) {
            return [
                'success' => false,
                'message' => 'ไม่มีข้อความที่จะส่ง',
                'error_code' => 'NO_MESSAGE'
            ];
        }
        
        // เตรียมข้อมูลสำหรับส่ง
        $data = [
            'to' => $lineId,
            'messages' => $messages
        ];
        
        // ทำการส่งข้อความ
        return $this->sendRequest($this->messageEndpoint, $data);
    }
    
    /**
     * ส่งข้อความไปยังผู้ใช้หลายคน
     * 
     * @param array $lineIds รายการ LINE User ID ของผู้รับข้อความ
     * @param array $messages ข้อความที่ต้องการส่ง (ในรูปแบบของ LINE Messaging API)
     * @return array ผลลัพธ์การส่ง
     */
    public function sendMulticast($lineIds, $messages) {
        // ตรวจสอบว่ามี LINE ID หรือไม่
        if (empty($lineIds) || !is_array($lineIds)) {
            return [
                'success' => false,
                'message' => 'ไม่พบรายการ LINE ID ของผู้รับ',
                'error_code' => 'NO_LINE_IDS'
            ];
        }
        
        // ตรวจสอบว่ามีข้อความหรือไม่
        if (empty($messages)) {
            return [
                'success' => false,
                'message' => 'ไม่มีข้อความที่จะส่ง',
                'error_code' => 'NO_MESSAGE'
            ];
        }
        
        // เตรียมข้อมูลสำหรับส่ง
        $data = [
            'to' => $lineIds,
            'messages' => $messages
        ];
        
        // ทำการส่งข้อความ
        return $this->sendRequest($this->multicastEndpoint, $data);
    }
    
    /**
     * ส่งข้อความข้อความแจ้งเตือนการเข้าแถวไปยังผู้ปกครอง
     * 
     * @param int $student_id รหัสนักเรียน
     * @param string $message ข้อความที่ต้องการส่ง
     * @param bool $includeChart แนบกราฟการเข้าแถว
     * @param bool $includeLink แนบลิงก์ดูข้อมูลเพิ่มเติม
     * @param string $startDate วันที่เริ่มต้นของข้อมูลการเข้าแถว (YYYY-MM-DD)
     * @param string $endDate วันที่สิ้นสุดของข้อมูลการเข้าแถว (YYYY-MM-DD)
     * @return array ผลลัพธ์การส่งข้อความ
     */
    public function sendAttendanceNotification($student_id, $message, $includeChart = true, $includeLink = true, $startDate = null, $endDate = null) {
        try {
            // ดึงข้อมูล LINE ID ของผู้ปกครองของนักเรียน
            $stmt = $this->conn->prepare("
                SELECT 
                    u.line_id,
                    u.first_name,
                    u.last_name,
                    s.title,
                    s.student_code,
                    c.level,
                    c.group_number,
                    d.department_name
                FROM 
                    parent_student_relation psr
                    JOIN parents p ON psr.parent_id = p.parent_id
                    JOIN users u ON p.user_id = u.user_id
                    JOIN students s ON psr.student_id = s.student_id
                    LEFT JOIN classes c ON s.current_class_id = c.class_id
                    LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE 
                    psr.student_id = ? AND
                    u.line_id IS NOT NULL
            ");
            $stmt->execute([$student_id]);
            $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($parents)) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบผู้ปกครองที่มี LINE ID',
                    'student_id' => $student_id
                ];
            }
            
            // ดึงข้อมูลการเข้าแถวของนักเรียน
            $attendanceData = $this->getStudentAttendanceData($student_id, $startDate, $endDate);
            
            // สร้างข้อความเฉพาะบุคคลสำหรับแต่ละผู้ปกครอง
            $results = [];
            $costPerParent = 0.075; // บาทต่อข้อความ
            if ($includeChart) $costPerParent += 0.15; // บาทต่อรูปภาพ
            if ($includeLink) $costPerParent += 0.075; // บาทต่อลิงก์
            
            // ดึงข้อมูลครูที่ปรึกษา
            $advisorInfo = $this->getAdvisorInfo($student_id);
            
            $totalCost = 0;
            $successCount = 0;
            
            foreach ($parents as $parent) {
                // เตรียมข้อมูลสำหรับแทนที่ตัวแปรในข้อความ
                $student_class = $parent['level'] . '/' . $parent['group_number'];
                $student_name = $parent['title'] . ' ' . $parent['first_name'] . ' ' . $parent['last_name'];
                
                // แทนที่ตัวแปรในข้อความ
                $personalizedMessage = $message;
                $personalizedMessage = str_replace('{{ชื่อนักเรียน}}', $student_name, $personalizedMessage);
                $personalizedMessage = str_replace('{{ชั้นเรียน}}', $student_class, $personalizedMessage);
                $personalizedMessage = str_replace('{{จำนวนวันเข้าแถว}}', $attendanceData['present_count'] ?? 0, $personalizedMessage);
                $personalizedMessage = str_replace('{{จำนวนวันทั้งหมด}}', $attendanceData['total_days'] ?? 0, $personalizedMessage);
                $personalizedMessage = str_replace('{{ร้อยละการเข้าแถว}}', $attendanceData['attendance_rate'] ?? 0, $personalizedMessage);
                $personalizedMessage = str_replace('{{จำนวนวันขาด}}', $attendanceData['absent_count'] ?? 0, $personalizedMessage);
                $personalizedMessage = str_replace('{{สถานะการเข้าแถว}}', $this->getAttendanceStatus($attendanceData['attendance_rate'] ?? 0), $personalizedMessage);
                $personalizedMessage = str_replace('{{ชื่อครูที่ปรึกษา}}', $advisorInfo['advisor_name'] ?? 'ไม่ระบุ', $personalizedMessage);
                $personalizedMessage = str_replace('{{เบอร์โทรครู}}', $advisorInfo['advisor_phone'] ?? 'ไม่ระบุ', $personalizedMessage);
                
                // สร้างข้อความในรูปแบบ LINE Message API
                $lineMessages = [
                    [
                        'type' => 'text',
                        'text' => $personalizedMessage
                    ]
                ];
                
                // เพิ่มกราฟการเข้าแถว (ถ้าต้องการ)
                if ($includeChart && !empty($attendanceData['chart_url'])) {
                    $lineMessages[] = [
                        'type' => 'image',
                        'originalContentUrl' => $attendanceData['chart_url'],
                        'previewImageUrl' => $attendanceData['chart_url']
                    ];
                }
                
                // เพิ่มลิงก์ดูข้อมูลเพิ่มเติม (ถ้าต้องการ)
                if ($includeLink) {
                    $detailUrl = $this->generateDetailUrl($student_id, $startDate, $endDate);
                    $lineMessages[] = [
                        'type' => 'template',
                        'altText' => 'ดูข้อมูลโดยละเอียด',
                        'template' => [
                            'type' => 'buttons',
                            'text' => 'ต้องการดูข้อมูลการเข้าแถวโดยละเอียด?',
                            'actions' => [
                                [
                                    'type' => 'uri',
                                    'label' => 'ดูข้อมูลโดยละเอียด',
                                    'uri' => $detailUrl
                                ]
                            ]
                        ]
                    ];
                }
                
                // เนื่องจากเราต้องการจำลองการส่ง สร้างผลลัพธ์จำลอง
                // ในระบบจริงต้องใช้ $this->sendMessage($parent['line_id'], $lineMessages)
                $result = [
                    'success' => true,
                    'message_id' => 'msg_' . uniqid(),
                    'recipient' => $parent['line_id'],
                    'cost' => $costPerParent
                ];
                
                // บันทึกประวัติการส่ง
                $this->logNotification($parent['line_id'], $personalizedMessage, $result['success']);
                
                $results[] = $result;
                $totalCost += $costPerParent;
                
                if ($result['success']) {
                    $successCount++;
                }
            }
            
            return [
                'success' => $successCount > 0,
                'student_id' => $student_id,
                'student_name' => $student_name,
                'class' => $student_class,
                'parent_count' => count($parents),
                'message_count' => count($parents),
                'results' => $results,
                'cost' => $totalCost,
                'success_count' => $successCount,
                'error_count' => count($parents) - $successCount
            ];
        } catch (PDOException $e) {
            error_log("LineNotificationAPI Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                'student_id' => $student_id
            ];
        }
    }
    
    /**
     * ส่งข้อความกลุ่มไปยังผู้ปกครองของนักเรียนหลายคน
     * 
     * @param array $student_ids รายการรหัสนักเรียน
     * @param string $message ข้อความที่ต้องการส่ง
     * @param bool $includeChart แนบกราฟการเข้าแถว
     * @param bool $includeLink แนบลิงก์ดูข้อมูลเพิ่มเติม
     * @param string $startDate วันที่เริ่มต้นของข้อมูลการเข้าแถว (YYYY-MM-DD)
     * @param string $endDate วันที่สิ้นสุดของข้อมูลการเข้าแถว (YYYY-MM-DD)
     * @return array ผลลัพธ์การส่งข้อความ
     */
    public function sendGroupAttendanceNotification($student_ids, $message, $includeChart = true, $includeLink = true, $startDate = null, $endDate = null) {
        if (empty($student_ids) || !is_array($student_ids)) {
            return [
                'success' => false,
                'message' => 'ไม่พบรายการนักเรียน',
                'error_code' => 'NO_STUDENTS'
            ];
        }
        
        // ส่งข้อความทีละคน (ในระบบจริงควรปรับปรุงให้มีประสิทธิภาพมากขึ้น)
        $results = [];
        $totalCost = 0;
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($student_ids as $student_id) {
            $result = $this->sendAttendanceNotification($student_id, $message, $includeChart, $includeLink, $startDate, $endDate);
            $results[] = $result;
            
            $totalCost += $result['cost'] ?? 0;
            $successCount += $result['success_count'] ?? 0;
            $errorCount += $result['error_count'] ?? 0;
        }
        
        return [
            'success' => $successCount > 0,
            'message' => "ส่งข้อความสำเร็จ $successCount รายการ, ล้มเหลว $errorCount รายการ",
            'results' => $results,
            'total_cost' => $totalCost,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ];
    }
    
    /**
     * ดึงข้อมูลการเข้าแถวของนักเรียน
     * 
     * @param int $student_id รหัสนักเรียน
     * @param string $startDate วันที่เริ่มต้น (YYYY-MM-DD)
     * @param string $endDate วันที่สิ้นสุด (YYYY-MM-DD)
     * @return array ข้อมูลการเข้าแถว
     */
    private function getStudentAttendanceData($student_id, $startDate = null, $endDate = null) {
        try {
            // ถ้าไม่ระบุช่วงวันที่ ใช้ข้อมูลภาคเรียนปัจจุบัน
            if (empty($startDate) || empty($endDate)) {
                $stmt = $this->conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
                $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($academicYear) {
                    $startDate = $academicYear['start_date'];
                    $endDate = $academicYear['end_date'];
                } else {
                    $startDate = date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
                    $endDate = date('Y-m-d'); // วันปัจจุบัน
                }
            }
            
            // ดึงข้อมูลการเข้าแถว
            $query = "
                SELECT 
                    COUNT(*) AS total_days,
                    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) AS late_count,
                    SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) AS leave_count
                FROM 
                    attendance
                WHERE 
                    student_id = ?
                    AND date BETWEEN ? AND ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$student_id, $startDate, $endDate]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_days = $data['total_days'] ?? 0;
            $present_count = $data['present_count'] ?? 0;
            $absent_count = $data['absent_count'] ?? 0;
            $late_count = $data['late_count'] ?? 0;
            $leave_count = $data['leave_count'] ?? 0;
            
            $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
            
            // สร้าง URL สำหรับกราฟ (ในระบบจริงควรสร้างกราฟจริงและอัปโหลดหรือส่งเป็น Base64)
            $chart_url = $this->generateChartUrl($student_id, $startDate, $endDate);
            
            return [
                'total_days' => $total_days,
                'present_count' => $present_count,
                'absent_count' => $absent_count,
                'late_count' => $late_count,
                'leave_count' => $leave_count,
                'attendance_rate' => $attendance_rate,
                'chart_url' => $chart_url,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        } catch (PDOException $e) {
            error_log("Error getting attendance data: " . $e->getMessage());
            return [
                'total_days' => 0,
                'present_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
                'leave_count' => 0,
                'attendance_rate' => 0,
                'chart_url' => '',
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        }
    }
    
    /**
     * ดึงข้อมูลครูที่ปรึกษาของนักเรียน
     * 
     * @param int $student_id รหัสนักเรียน
     * @return array ข้อมูลครูที่ปรึกษา
     */
    private function getAdvisorInfo($student_id) {
        try {
            $query = "
                SELECT 
                    t.teacher_id,
                    t.title,
                    t.first_name,
                    t.last_name,
                    u.phone_number
                FROM 
                    students s
                    JOIN classes c ON s.current_class_id = c.class_id
                    JOIN class_advisors ca ON c.class_id = ca.class_id
                    JOIN teachers t ON ca.teacher_id = t.teacher_id
                    JOIN users u ON t.user_id = u.user_id
                WHERE 
                    s.student_id = ?
                    AND ca.is_primary = 1
                LIMIT 1
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$student_id]);
            $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($advisor) {
                return [
                    'advisor_id' => $advisor['teacher_id'],
                    'advisor_name' => $advisor['title'] . ' ' . $advisor['first_name'] . ' ' . $advisor['last_name'],
                    'advisor_phone' => $advisor['phone_number'] ?? 'ไม่ระบุ'
                ];
            } else {
                return [
                    'advisor_id' => 0,
                    'advisor_name' => 'ไม่ระบุ',
                    'advisor_phone' => 'ไม่ระบุ'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error getting advisor info: " . $e->getMessage());
            return [
                'advisor_id' => 0,
                'advisor_name' => 'ไม่ระบุ',
                'advisor_phone' => 'ไม่ระบุ'
            ];
        }
    }
    
    /**
     * สร้าง URL สำหรับกราฟการเข้าแถว
     * 
     * @param int $student_id รหัสนักเรียน
     * @param string $startDate วันที่เริ่มต้น
     * @param string $endDate วันที่สิ้นสุด
     * @return string URL ของกราฟ
     */
    private function generateChartUrl($student_id, $startDate, $endDate) {
        // ในระบบจริงควรสร้างกราฟจริงและอัปโหลดหรือส่งเป็น Base64
        // ตัวอย่างนี้ใช้ Placeholder API ของ unsplash.com
        return 'https://via.placeholder.com/1024x768.png/28a745/FFFFFF?text=กราฟการเข้าแถว+' . $student_id;
    }
    
    /**
     * สร้าง URL สำหรับดูข้อมูลโดยละเอียด
     * 
     * @param int $student_id รหัสนักเรียน
     * @param string $startDate วันที่เริ่มต้น
     * @param string $endDate วันที่สิ้นสุด
     * @return string URL สำหรับดูข้อมูลโดยละเอียด
     */
    private function generateDetailUrl($student_id, $startDate, $endDate) {
        // ตัวอย่าง URL สำหรับดูข้อมูลโดยละเอียด
        $baseUrl = "https://your-domain.com/attendance-detail.php";
        $params = http_build_query([
            'student_id' => $student_id,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $baseUrl . '?' . $params;
    }
    
    /**
     * ส่งคำขอไปยัง LINE Messaging API
     * 
     * @param string $endpoint URL ของ API endpoint
     * @param array $data ข้อมูลที่จะส่ง
     * @return array ผลลัพธ์การส่ง
     */
    private function sendRequest($endpoint, $data) {
        // จำลองการส่งข้อความสำเร็จ (ในระบบจริงควรใช้ cURL หรือ Guzzle HTTP Client)
        // ไม่ได้ส่งจริงในตัวอย่างนี้
        error_log('Simulating sending message to LINE API: ' . json_encode($data));
        
        return [
            'success' => true,
            'message_id' => 'msg_' . uniqid()
        ];
        
        /* สำหรับระบบจริง ต้องใช้โค้ดนี้:
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log('cURL Error: ' . $error);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $error,
                'error_code' => 'CURL_ERROR'
            ];
        }
        
        if ($httpCode != 200) {
            error_log('LINE API Error: HTTP ' . $httpCode . ' - ' . $response);
            return [
                'success' => false,
                'message' => 'LINE API ตอบกลับด้วยสถานะ: ' . $httpCode,
                'error_code' => 'API_ERROR',
                'http_code' => $httpCode,
                'response' => $response
            ];
        }
        
        $result = json_decode($response, true);
        
        return [
            'success' => true,
            'message_id' => $result['message_id'] ?? uniqid()
        ];
        */
    }
    
    /**
     * บันทึกประวัติการส่งข้อความ
     * 
     * @param string $line_id LINE ID ของผู้รับ
     * @param string $message ข้อความที่ส่ง
     * @param bool $success สถานะการส่ง
     * @return bool ผลลัพธ์การบันทึก
     */
    private function logNotification($line_id, $message, $success = true) {
        try {
            $query = "
                INSERT INTO line_notifications 
                (user_id, message, sent_at, status, notification_type) 
                VALUES 
                ((SELECT user_id FROM users WHERE line_id = ?), ?, NOW(), ?, 'attendance')
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $line_id,
                $message,
                $success ? 'sent' : 'failed'
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error logging notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ดึงสถานะการเข้าแถวตามอัตราการเข้าแถว
     * 
     * @param float $rate อัตราการเข้าแถว (%)
     * @return string สถานะการเข้าแถว
     */
    private function getAttendanceStatus($rate) {
        if ($rate < 60) return 'เสี่ยงตกกิจกรรม';
        if ($rate < 80) return 'ต้องระวัง';
        return 'ปกติ';
    }
}
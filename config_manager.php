<?php
/**
 * config_manager.php - ระบบดึงและจัดการการตั้งค่าต่างๆ ของระบบน้องชูใจ AI
 */
require_once 'db_connect.php';
class ConfigManager {
    private static $instance = null;
    private $settings = [];
    private $db = null;

    // Constructor เป็น private เพื่อบังคับใช้ Singleton pattern
    private function __construct() {
        // เชื่อมต่อฐานข้อมูล
        try {
            $this->db = getDB(); // ใช้ฟังก์ชัน getDB() จาก db_connect.php
            
            // ดึงการตั้งค่าทั้งหมดจากฐานข้อมูล
            $stmt = $this->db->prepare("SELECT setting_key, setting_value, setting_group FROM system_settings");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log("ConfigManager Error: " . $e->getMessage());
        }
    }

    // ใช้ Singleton pattern เพื่อให้มี instance เดียว
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ConfigManager();
        }
        return self::$instance;
    }

    /**
     * ดึงค่าการตั้งค่าตามคีย์
     * @param string $key คีย์การตั้งค่า
     * @param mixed $default ค่าเริ่มต้นหากไม่พบการตั้งค่า
     * @return mixed ค่าการตั้งค่า หรือค่าเริ่มต้นหากไม่พบ
     */
    public function getSetting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * ดึงค่าการตั้งค่าแบบบูลีน (0/1 เป็น true/false)
     * @param string $key คีย์การตั้งค่า
     * @param bool $default ค่าเริ่มต้นหากไม่พบการตั้งค่า
     * @return bool ค่าการตั้งค่าแบบบูลีน
     */
    public function getBoolSetting($key, $default = false) {
        if (!isset($this->settings[$key])) {
            return $default;
        }
        
        $value = $this->settings[$key];
        return ($value === '1' || $value === 1 || $value === true || strtolower($value) === 'true');
    }

    /**
     * ดึงค่าการตั้งค่าแบบตัวเลข
     * @param string $key คีย์การตั้งค่า
     * @param int $default ค่าเริ่มต้นหากไม่พบการตั้งค่า
     * @return int ค่าการตั้งค่าแบบตัวเลข
     */
    public function getIntSetting($key, $default = 0) {
        if (!isset($this->settings[$key])) {
            return $default;
        }
        
        return intval($this->settings[$key]);
    }

    /**
     * ดึงการตั้งค่า LINE
     * @return array การตั้งค่า LINE ทั้งหมด
     */
    public function getLineSettings() {
        $singleLineOA = $this->getBoolSetting('single_line_oa', true);
        
        if ($singleLineOA) {
            // กรณีใช้ LINE OA เดียว
            return [
                'client_id' => $this->getSetting('line_channel_id', '2007088707'),
                'client_secret' => $this->getSetting('line_channel_secret', 'ebd6dffa14e54908a835c59c3bd3a7cf'),
                'redirect_uri' => $this->getSetting('liff_url', 'https://your-domain.com/line-oa/callback.php'),
                'liff_id' => $this->getSetting('liff_id', '2007088707-5EJ0XDlr'),
                'access_token' => $this->getSetting('line_access_token', ''),
                'oa_name' => $this->getSetting('line_oa_name', 'น้องชูใจ AI'),
                'welcome_message' => $this->getSetting('line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน')
            ];
        } else {
            // กรณีใช้หลาย LINE OA
            return [
                'student' => [
                    'client_id' => $this->getSetting('student_line_channel_id', ''),
                    'client_secret' => $this->getSetting('student_line_channel_secret', ''),
                    'redirect_uri' => $this->getSetting('liff_url', 'https://your-domain.com/line-oa/callback.php'),
                    'liff_id' => $this->getSetting('liff_id', ''),
                    'access_token' => $this->getSetting('student_line_access_token', ''),
                    'oa_name' => $this->getSetting('student_line_oa_name', 'STD-Prasat'),
                    'welcome_message' => $this->getSetting('student_line_welcome_message', 'ยินดีต้อนรับนักเรียนสู่ระบบน้องชูใจ AI')
                ],
                'teacher' => [
                    'client_id' => $this->getSetting('teacher_line_channel_id', ''),
                    'client_secret' => $this->getSetting('teacher_line_channel_secret', ''),
                    'redirect_uri' => $this->getSetting('liff_url', 'https://your-domain.com/line-oa/callback.php'),
                    'liff_id' => $this->getSetting('liff_id', ''),
                    'access_token' => $this->getSetting('teacher_line_access_token', ''),
                    'oa_name' => $this->getSetting('teacher_line_oa_name', 'Teacher-Prasat'),
                    'welcome_message' => $this->getSetting('teacher_line_welcome_message', 'ยินดีต้อนรับครูสู่ระบบน้องชูใจ AI')
                ],
                'parent' => [
                    'client_id' => $this->getSetting('parent_line_channel_id', ''),
                    'client_secret' => $this->getSetting('parent_line_channel_secret', ''),
                    'redirect_uri' => $this->getSetting('liff_url', 'https://your-domain.com/line-oa/callback.php'),
                    'liff_id' => $this->getSetting('liff_id', ''),
                    'access_token' => $this->getSetting('parent_line_access_token', ''),
                    'oa_name' => $this->getSetting('parent_line_oa_name', 'SADD-Prasat'),
                    'welcome_message' => $this->getSetting('parent_line_welcome_message', 'ยินดีต้อนรับผู้ปกครองสู่ระบบน้องชูใจ AI')
                ]
            ];
        }
    }

    /**
     * ดึงการตั้งค่า SMS
     * @return array การตั้งค่า SMS ทั้งหมด
     */
    public function getSmsSettings() {
        return [
            'enabled' => $this->getBoolSetting('enable_sms', false),
            'provider' => $this->getSetting('sms_provider', 'thsms'),
            'api_key' => $this->getSetting('sms_api_key', ''),
            'api_secret' => $this->getSetting('sms_api_secret', ''),
            'api_url' => $this->getSetting('sms_api_url', 'https://api.thsms.com/api/send'),
            'sender_id' => $this->getSetting('sms_sender_id', 'PRASAT'),
            'use_unicode' => $this->getBoolSetting('sms_use_unicode', true),
            'max_length' => $this->getIntSetting('sms_max_length', 160),
            'daily_limit' => $this->getIntSetting('sms_daily_limit', 100),
            'absence_template' => $this->getSetting('sms_absence_template', 'แจ้งการขาดแถว: นักเรียน {student_name} ขาดการเข้าแถวจำนวน {absent_count} ครั้ง')
        ];
    }

    /**
     * ดึงการตั้งค่าการเข้าแถว
     * @return array การตั้งค่าการเข้าแถวทั้งหมด
     */
    public function getAttendanceSettings() {
        $min_rate = $this->getSetting('min_attendance_rate', '80');
        
        if ($min_rate === 'custom') {
            $min_rate = $this->getIntSetting('custom_attendance_rate', 80);
        } else {
            $min_rate = intval($min_rate);
        }
        
        return [
            'min_attendance_rate' => $min_rate,
            'required_days' => $this->getIntSetting('required_attendance_days', 80),
            'counting_period' => $this->getSetting('attendance_counting_period', 'semester'),
            'count_weekend' => $this->getBoolSetting('count_weekend', true),
            'count_holidays' => $this->getBoolSetting('count_holidays', false),
            'exemption_dates' => $this->getSetting('exemption_dates', ''),
            'start_time' => $this->getSetting('attendance_start_time', '07:30'),
            'end_time' => $this->getSetting('attendance_end_time', '08:20'),
            'late_check' => $this->getBoolSetting('late_check', true),
            'late_duration' => $this->getIntSetting('late_check_duration', 30),
            'pin_enabled' => $this->getBoolSetting('enable_pin', true),
            'qr_enabled' => $this->getBoolSetting('enable_qr', true),
            'gps_enabled' => $this->getBoolSetting('enable_gps', true),
            'photo_enabled' => $this->getBoolSetting('enable_photo', false),
            'manual_enabled' => $this->getBoolSetting('enable_manual', true)
        ];
    }

    /**
     * ดึงการตั้งค่า GPS
     * @return array การตั้งค่า GPS ทั้งหมด
     */
    public function getGpsSettings() {
        $radius = $this->getSetting('gps_radius', '100');
        
        if ($radius === 'custom') {
            $radius = $this->getIntSetting('custom_gps_radius', 100);
        } else {
            $radius = intval($radius);
        }
        
        return [
            'school_latitude' => $this->getSetting('school_latitude', '14.9523'),
            'school_longitude' => $this->getSetting('school_longitude', '103.4919'),
            'radius' => $radius,
            'accuracy' => $this->getIntSetting('gps_accuracy', 10),
            'check_interval' => $this->getIntSetting('gps_check_interval', 5),
            'required' => $this->getBoolSetting('gps_required', true),
            'photo_required' => $this->getBoolSetting('gps_photo_required', false),
            'mock_detection' => $this->getBoolSetting('gps_mock_detection', true),
            'allow_home_check' => $this->getBoolSetting('allow_home_check', false),
            'multiple_locations' => $this->getBoolSetting('enable_multiple_locations', false)
        ];
    }

    /**
     * ดึงการตั้งค่า Webhook
     * @return array การตั้งค่า Webhook ทั้งหมด
     */
    public function getWebhookSettings() {
        return [
            'enabled' => $this->getBoolSetting('enable_webhook', true),
            'url' => $this->getSetting('webhook_url', 'https://your-domain.com/line-oa/webhook.php'),
            'secret' => $this->getSetting('webhook_secret', ''),
            'auto_reply' => $this->getBoolSetting('enable_auto_reply', true),
            'initial_greeting' => $this->getSetting('initial_greeting', 'สวัสดีครับ/ค่ะ ยินดีต้อนรับสู่ระบบน้องชูใจ AI'),
            'fallback_message' => $this->getSetting('fallback_message', 'ขออภัยครับ/ค่ะ ระบบไม่เข้าใจคำสั่ง โปรดลองใหม่อีกครั้ง')
        ];
    }

    /**
     * บันทึกการตั้งค่า
     * @param string $key คีย์การตั้งค่า
     * @param mixed $value ค่าการตั้งค่า
     * @param string $group หมวดหมู่การตั้งค่า
     * @return bool สถานะการบันทึก
     */
    public function saveSetting($key, $value, $group = 'general') {
        try {
            // ตรวจสอบว่ามีการตั้งค่าอยู่แล้วหรือไม่
            $stmt = $this->db->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                // อัปเดตการตั้งค่าที่มีอยู่
                $stmt = $this->db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                // เพิ่มการตั้งค่าใหม่
                $stmt = $this->db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$key, $value, $group]);
            }
            
            // อัปเดตค่าในแคช
            $this->settings[$key] = $value;
            
            return true;
        } catch (PDOException $e) {
            error_log("Error saving setting: " . $e->getMessage());
            return false;
        }
    }

    /**
     * บันทึกการตั้งค่าหลายรายการ
     * @param array $settings รายการการตั้งค่า (key => value)
     * @param string $group หมวดหมู่การตั้งค่า
     * @return bool สถานะการบันทึก
     */
    public function saveSettings($settings, $group = 'general') {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $this->saveSetting($key, $value, $group);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error saving settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึงค่าปีการศึกษาปัจจุบัน
     * @return array ข้อมูลปีการศึกษาปัจจุบัน
     */
    public function getCurrentAcademicYear() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
            $stmt->execute();
            $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($academicYear) {
                return $academicYear;
            } else {
                // ถ้าไม่พบปีการศึกษาที่ active ให้ดึงปีล่าสุด
                $stmt = $this->db->prepare("SELECT * FROM academic_years ORDER BY year DESC, semester DESC LIMIT 1");
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log("Error getting current academic year: " . $e->getMessage());
            return null;
        }
    }

    // ป้องกันการ clone object
    private function __clone() {}
}
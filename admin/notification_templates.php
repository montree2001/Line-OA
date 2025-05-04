<?php
/**
 * notification_templates.php - จัดการเทมเพลตข้อความแจ้งเตือน
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI ดูแลผู้เรียน
 * @version 1.0
 */

// ตรวจสอบว่าเรียกใช้งานโดยตรงหรือไม่
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    die('การเข้าถึงโดยตรงไม่ได้รับอนุญาต');
}

// เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../db_connect.php';

/**
 * คลาสจัดการเทมเพลตข้อความ
 */
class NotificationTemplates {
    private $conn;

    /**
     * Constructor
     */
    public function __construct() {
        $this->conn = getDB();
        
        // ตรวจสอบว่ามีตารางเทมเพลตหรือไม่ ถ้าไม่มีให้สร้าง
        $this->createTemplateTableIfNotExists();
    }

    /**
     * ดึงเทมเพลตทั้งหมด
     * 
     * @param string $type ประเภทเทมเพลต (individual, group หรือ all)
     * @param string $category หมวดหมู่เทมเพลต (attendance, meeting, activity, other หรือ all)
     * @param bool $active_only ดึงเฉพาะเทมเพลตที่ใช้งานอยู่
     * @return array เทมเพลตทั้งหมด
     */
    public function getAllTemplates($type = 'all', $category = 'all', $active_only = false) {
        $sql = "SELECT * FROM message_templates WHERE 1=1";
        $params = [];
        
        if ($type !== 'all') {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        if ($category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($active_only) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllTemplates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงข้อมูลเทมเพลตตาม ID
     * 
     * @param int $template_id รหัสเทมเพลต
     * @return array|null ข้อมูลเทมเพลต
     */
    public function getTemplateById($template_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM message_templates WHERE id = ?");
            $stmt->execute([$template_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getTemplateById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * บันทึกเทมเพลตข้อความ
     * 
     * @param string $name ชื่อเทมเพลต
     * @param string $type ประเภทเทมเพลต (individual, group)
     * @param string $category หมวดหมู่เทมเพลต
     * @param string $content เนื้อหาเทมเพลต
     * @param int $template_id รหัสเทมเพลต (กรณีอัปเดต)
     * @param int $created_by รหัสผู้สร้าง
     * @return array ผลลัพธ์การบันทึก
     */
    public function saveTemplate($name, $type, $category, $content, $template_id = null, $created_by = null) {
        try {
            // ตรวจสอบว่ามีชื่อซ้ำหรือไม่
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM message_templates WHERE name = ? AND id != ?");
            $stmt->execute([$name, $template_id ?? 0]);
            $exists = $stmt->fetchColumn() > 0;
            
            if ($exists) {
                return [
                    'success' => false,
                    'message' => 'มีเทมเพลตชื่อนี้อยู่แล้ว'
                ];
            }
            
            // ถ้ามี ID ให้อัปเดต ถ้าไม่มีให้เพิ่มใหม่
            if ($template_id) {
                $stmt = $this->conn->prepare("
                    UPDATE message_templates 
                    SET name = ?, type = ?, category = ?, content = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $type, $category, $content, $template_id]);
                
                return [
                    'success' => true,
                    'message' => 'อัปเดตเทมเพลตเรียบร้อยแล้ว',
                    'template_id' => $template_id
                ];
            } else {
                $stmt = $this->conn->prepare("
                    INSERT INTO message_templates (name, type, category, content, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $type, $category, $content, $created_by]);
                
                return [
                    'success' => true,
                    'message' => 'สร้างเทมเพลตใหม่เรียบร้อยแล้ว',
                    'template_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Error in saveTemplate: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ลบเทมเพลตข้อความ
     * 
     * @param int $template_id รหัสเทมเพลต
     * @return array ผลลัพธ์การลบ
     */
    public function deleteTemplate($template_id) {
        try {
            // ตรวจสอบว่ามีเทมเพลตนี้หรือไม่
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM message_templates WHERE id = ?");
            $stmt->execute([$template_id]);
            $exists = $stmt->fetchColumn() > 0;
            
            if (!$exists) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบเทมเพลตนี้'
                ];
            }
            
            // ลบเทมเพลต
            $stmt = $this->conn->prepare("DELETE FROM message_templates WHERE id = ?");
            $stmt->execute([$template_id]);
            
            return [
                'success' => true,
                'message' => 'ลบเทมเพลตเรียบร้อยแล้ว'
            ];
        } catch (PDOException $e) {
            error_log("Error in deleteTemplate: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * อัปเดตสถานะเทมเพลต (ใช้งาน/ไม่ใช้งาน)
     * 
     * @param int $template_id รหัสเทมเพลต
     * @param bool $is_active สถานะการใช้งาน
     * @return array ผลลัพธ์การอัปเดต
     */
    public function updateTemplateStatus($template_id, $is_active) {
        try {
            // ตรวจสอบว่ามีเทมเพลตนี้หรือไม่
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM message_templates WHERE id = ?");
            $stmt->execute([$template_id]);
            $exists = $stmt->fetchColumn() > 0;
            
            if (!$exists) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบเทมเพลตนี้'
                ];
            }
            
            // อัปเดตสถานะ
            $stmt = $this->conn->prepare("UPDATE message_templates SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$is_active ? 1 : 0, $template_id]);
            
            return [
                'success' => true,
                'message' => 'อัปเดตสถานะเทมเพลตเรียบร้อยแล้ว'
            ];
        } catch (PDOException $e) {
            error_log("Error in updateTemplateStatus: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * อัปเดตวันที่ใช้งานล่าสุดของเทมเพลต
     * 
     * @param int $template_id รหัสเทมเพลต
     * @return bool สถานะการอัปเดต
     */
    public function updateTemplateLastUsed($template_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE message_templates SET last_used = NOW() WHERE id = ?");
            $stmt->execute([$template_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error in updateTemplateLastUsed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึงข้อมูลเทมเพลตตามประเภทและหมวดหมู่
     * 
     * @param string $type ประเภทเทมเพลต (individual, group)
     * @param string $category หมวดหมู่เทมเพลต
     * @return array เทมเพลตที่ตรงกับเงื่อนไข
     */
    public function getTemplatesByTypeAndCategory($type, $category = null) {
        $sql = "SELECT * FROM message_templates WHERE type = ? AND is_active = 1";
        $params = [$type];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY name ASC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getTemplatesByTypeAndCategory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงจำนวนเทมเพลตในแต่ละหมวดหมู่
     * 
     * @return array จำนวนเทมเพลตในแต่ละหมวดหมู่
     */
    public function getTemplateCategoryCounts() {
        try {
            $stmt = $this->conn->query("
                SELECT category, COUNT(*) as count 
                FROM message_templates 
                GROUP BY category
            ");
            
            $counts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[$row['category']] = $row['count'];
            }
            
            return $counts;
        } catch (PDOException $e) {
            error_log("Error in getTemplateCategoryCounts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * สร้างตารางเทมเพลตในฐานข้อมูล (ถ้ายังไม่มี)
     * 
     * @return bool สถานะการสร้างตาราง
     */
    private function createTemplateTableIfNotExists() {
        try {
            // ตรวจสอบว่ามีตารางเทมเพลตหรือไม่
            $stmt = $this->conn->query("SHOW TABLES LIKE 'message_templates'");
            if ($stmt->rowCount() == 0) {
                // สร้างตารางเทมเพลต
                $this->conn->exec("
                    CREATE TABLE `message_templates` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL,
                      `type` enum('individual','group') NOT NULL,
                      `category` varchar(50) DEFAULT 'attendance',
                      `content` text NOT NULL,
                      `is_active` tinyint(1) NOT NULL DEFAULT 1,
                      `created_by` int(11) DEFAULT NULL,
                      `created_at` datetime NOT NULL,
                      `updated_at` datetime DEFAULT NULL,
                      `last_used` datetime DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                
                // เพิ่มเทมเพลตตัวอย่าง
                $this->createDefaultTemplates();
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error creating template table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * สร้างเทมเพลตตัวอย่างเริ่มต้น
     */
    private function createDefaultTemplates() {
        $default_templates = [
            [
                'name' => 'แจ้งเตือนความเสี่ยงรายบุคคล',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'นัดประชุมผู้ปกครองกลุ่มเสี่ยง',
                'type' => 'group',
                'category' => 'meeting',
                'content' => "เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'แจ้งเตือนฉุกเฉิน',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'รายงานสรุปประจำเดือน',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน{{เดือน}} {{ปี}}\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'แจ้งข่าวกิจกรรมวิทยาลัย',
                'type' => 'group',
                'category' => 'activity',
                'content' => "เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งข้อมูลข่าวสารเกี่ยวกับกิจกรรม{{ชื่อกิจกรรม}} ซึ่งจะจัดขึ้นในวันที่ {{วันที่}} เวลา {{เวลา}} ณ {{สถานที่}}\n\nนักเรียนจะต้อง{{รายละเอียด}}\n\nหากมีข้อสงสัยกรุณาติดต่อ {{ผู้รับผิดชอบ}} ที่เบอร์โทร {{เบอร์โทร}}\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ]
        ];
        
        foreach ($default_templates as $template) {
            $stmt = $this->conn->prepare("
                INSERT INTO message_templates (name, type, category, content, is_active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $template['name'],
                $template['type'],
                $template['category'],
                $template['content']
            ]);
        }
    }

    /**
     * ดึงเทมเพลตข้อความทั้งหมดในรูปแบบของตัวเลือก
     * 
     * @param string $type ประเภทเทมเพลต (individual, group)
     * @return string HTML ของตัวเลือก
     */
    public function getTemplateOptions($type) {
        $templates = $this->getTemplatesByTypeAndCategory($type);
        
        $options = '';
        foreach ($templates as $template) {
            $options .= '<option value="' . $template['id'] . '">' . htmlspecialchars($template['name']) . '</option>';
        }
        
        return $options;
    }
}
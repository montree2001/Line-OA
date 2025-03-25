<?php
/**
 * Teacher.php - คลาสสำหรับจัดการข้อมูลครูที่ปรึกษา
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

class Teacher {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * ดึงข้อมูลครูทั้งหมด
     * 
     * @param int $limit จำนวนรายการที่ต้องการดึง
     * @param int $offset ตำแหน่งเริ่มต้น
     * @param array $filters เงื่อนไขการกรอง
     * @return array ข้อมูลครูทั้งหมด
     */
    public function getAllTeachers($limit = 100, $offset = 0, $filters = []) {
        try {
            // ตรวจสอบว่ามีคอลัมน์ is_active ในตาราง users หรือไม่
            $hasIsActiveColumn = false;
            $columnsStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            if ($columnsStmt->rowCount() > 0) {
                $hasIsActiveColumn = true;
            }
            
            $whereClause = '';
            $params = [];
            
            // สร้างเงื่อนไข WHERE ตาม filters
            if (!empty($filters)) {
                $conditions = [];
                
                // กรองตามแผนก (department)
                if (!empty($filters['department'])) {
                    $conditions[] = "t.department = :department";
                    $params[':department'] = $filters['department'];
                }
                
                // กรองตามสถานะ (active/inactive) - ใช้เฉพาะเมื่อมีคอลัมน์ is_active
                if (isset($filters['status']) && $hasIsActiveColumn) {
                    $status = $filters['status'] === 'active' ? 1 : 0;
                    $conditions[] = "u.is_active = :status";
                    $params[':status'] = $status;
                }
                
                // กรองตามคำค้นหา (ชื่อหรือรหัส)
                if (!empty($filters['search'])) {
                    $conditions[] = "(t.first_name LIKE :search OR t.last_name LIKE :search OR t.national_id LIKE :search)";
                    $params[':search'] = '%' . $filters['search'] . '%';
                }
                
                if (!empty($conditions)) {
                    $whereClause = "WHERE " . implode(' AND ', $conditions);
                }
            }
            
            // สร้าง SQL ตามโครงสร้างตารางที่มี
            $sql = "SELECT t.teacher_id, t.national_id, t.title, t.first_name, t.last_name, 
                    t.department, t.position, u.phone_number, u.email";
            
            // เพิ่มคอลัมน์ is_active หากมี
            if ($hasIsActiveColumn) {
                $sql .= ", u.is_active";
            } else {
                // ถ้าไม่มีให้ใช้ค่าคงที่แทน (ถือว่าทุกคนเป็น active)
                $sql .= ", 1 as is_active";
            }
            
            // เพิ่มคอลัมน์ที่เหลือ
            $sql .= ", (SELECT COUNT(DISTINCT ca.class_id) 
                        FROM class_advisors ca 
                        WHERE ca.teacher_id = t.teacher_id) as class_count,
                    (SELECT GROUP_CONCAT(DISTINCT CONCAT(c.level, '/', c.group_number)) 
                        FROM class_advisors ca 
                        INNER JOIN classes c ON ca.class_id = c.class_id 
                        WHERE ca.teacher_id = t.teacher_id) as classes";
            
            // ต่อท้าย SQL
            $sql .= " FROM teachers t
                    INNER JOIN users u ON t.user_id = u.user_id
                    $whereClause
                    ORDER BY t.first_name
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // ผูกพารามิเตอร์เพิ่มเติมตาม filters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            // ดึงข้อมูลนักเรียนที่ดูแลโดยครูแต่ละคน
            $teachers = $stmt->fetchAll();
            foreach ($teachers as &$teacher) {
                // ดึงจำนวนนักเรียนที่ดูแล (ถ้ามีข้อมูลห้องเรียนที่เป็นที่ปรึกษา)
                if (!empty($teacher['classes'])) {
                    $classIds = [];
                    $stmt = $this->db->prepare("SELECT class_id FROM class_advisors WHERE teacher_id = :teacher_id");
                    $stmt->bindValue(':teacher_id', $teacher['teacher_id']);
                    $stmt->execute();
                    while ($row = $stmt->fetch()) {
                        $classIds[] = $row['class_id'];
                    }
                    
                    if (!empty($classIds)) {
                        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
                        $stmt = $this->db->prepare("SELECT COUNT(*) as students_count FROM students 
                                                   WHERE current_class_id IN ($placeholders) AND status = 'กำลังศึกษา'");
                        foreach ($classIds as $index => $id) {
                            $stmt->bindValue($index + 1, $id);
                        }
                        $stmt->execute();
                        $result = $stmt->fetch();
                        $teacher['students_count'] = $result['students_count'];
                    } else {
                        $teacher['students_count'] = 0;
                    }
                } else {
                    $teacher['students_count'] = 0;
                }
            }
            
            return $teachers;
        } catch (PDOException $e) {
            // บันทึกข้อผิดพลาดหรือจัดการตามเหมาะสม
            error_log("Database error in getAllTeachers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงข้อมูลสถิติเกี่ยวกับครู
     * 
     * @return array ข้อมูลสถิติ
     */
    public function getTeacherStats() {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'classrooms' => 0,
            'students' => 0
        ];
        
        // นับจำนวนครูทั้งหมด
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM teachers");
        $result = $stmt->fetch();
        $stats['total'] = $result['total'];
        
        // เช็คว่ามีคอลัมน์ is_active หรือไม่
        $hasIsActiveColumn = false;
        $columnsStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        if ($columnsStmt->rowCount() > 0) {
            $hasIsActiveColumn = true;
            
            // นับจำนวนครูที่กำลังปฏิบัติงาน
            $stmt = $this->db->query("SELECT COUNT(*) as active FROM teachers t 
                                      INNER JOIN users u ON t.user_id = u.user_id 
                                      WHERE u.is_active = 1");
            $result = $stmt->fetch();
            $stats['active'] = $result['active'];
            
            // นับจำนวนครูที่ไม่ได้ปฏิบัติงาน
            $stats['inactive'] = $stats['total'] - $stats['active'];
        } else {
            // ถ้าไม่มีคอลัมน์ is_active ถือว่าทุกคนเป็น active
            $stats['active'] = $stats['total'];
            $stats['inactive'] = 0;
        }
        
        // นับจำนวนห้องเรียนที่มีครูที่ปรึกษา
        $stmt = $this->db->query("SELECT COUNT(DISTINCT class_id) as classrooms FROM class_advisors");
        $result = $stmt->fetch();
        $stats['classrooms'] = $result['classrooms'];
        
        // นับจำนวนนักเรียนทั้งหมด
        $stmt = $this->db->query("SELECT COUNT(*) as students FROM students WHERE status = 'กำลังศึกษา'");
        $result = $stmt->fetch();
        $stats['students'] = $result['students'];
        
        return $stats;
    }
    
    /**
     * ดึงข้อมูลครูตาม ID
     * 
     * @param int $teacher_id รหัสครู
     * @return array ข้อมูลครู
     */
    public function getTeacherById($teacher_id) {
        try {
            // ตรวจสอบว่ามีคอลัมน์ is_active ในตาราง users หรือไม่
            $hasIsActiveColumn = false;
            $columnsStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            if ($columnsStmt->rowCount() > 0) {
                $hasIsActiveColumn = true;
            }
            
            // สร้าง SQL ตามโครงสร้างตารางที่มี
            $sql = "SELECT t.teacher_id, t.national_id, t.title, t.first_name, t.last_name, 
                   t.department, t.position, u.phone_number, u.email";
            
            // เพิ่มคอลัมน์ is_active หากมี
            if ($hasIsActiveColumn) {
                $sql .= ", u.is_active";
            } else {
                // ถ้าไม่มีให้ใช้ค่าคงที่แทน
                $sql .= ", 1 as is_active";
            }
            
            // เพิ่ม SQL ส่วนที่เหลือ
            $sql .= ", (SELECT GROUP_CONCAT(DISTINCT CONCAT(c.level, '/', c.group_number)) 
                       FROM class_advisors ca 
                       INNER JOIN classes c ON ca.class_id = c.class_id 
                       WHERE ca.teacher_id = t.teacher_id) as classes,
                    (SELECT class_id 
                     FROM class_advisors 
                     WHERE teacher_id = t.teacher_id 
                     LIMIT 1) as class_id
                    FROM teachers t
                    INNER JOIN users u ON t.user_id = u.user_id
                    WHERE t.teacher_id = :teacher_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $teacher = $stmt->fetch();
            
            // ดึงข้อมูลนักเรียนที่ดูแล (ถ้ามีข้อมูลห้องเรียน)
            if ($teacher && !empty($teacher['class_id'])) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as students_count FROM students 
                                          WHERE current_class_id = :class_id AND status = 'กำลังศึกษา'");
                $stmt->bindValue(':class_id', $teacher['class_id']);
                $stmt->execute();
                $result = $stmt->fetch();
                $teacher['students_count'] = $result['students_count'];
            } else if ($teacher) {
                $teacher['students_count'] = 0;
            }
            
            return $teacher;
        } catch (PDOException $e) {
            error_log("Database error in getTeacherById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * เพิ่มครูใหม่
     * 
     * @param array $data ข้อมูลครูที่ต้องการเพิ่ม
     * @return int รหัสครูที่เพิ่ม
     */
    public function addTeacher($data) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบว่าตาราง users มีคอลัมน์ is_active หรือไม่
            $columnsStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            $hasIsActiveColumn = $columnsStmt->rowCount() > 0;
            
            // ถ้าไม่มีและสามารถเพิ่มได้ ให้เพิ่มคอลัมน์ is_active
            if (!$hasIsActiveColumn) {
                try {
                    $this->db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER email");
                    $this->db->exec("UPDATE users SET is_active = 1");
                    $hasIsActiveColumn = true;
                } catch (PDOException $e) {
                    // บันทึกข้อผิดพลาดแต่ไม่หยุดการทำงาน
                    error_log("ไม่สามารถเพิ่มคอลัมน์ is_active: " . $e->getMessage());
                }
            }
            
            // สร้าง SQL สำหรับเพิ่มข้อมูลในตาราง users
            $userSql = "INSERT INTO users (line_id, role, title, first_name, last_name, 
                                phone_number, email";
            
            // เพิ่มคอลัมน์ is_active หากมี
            if ($hasIsActiveColumn) {
                $userSql .= ", is_active";
            }
            
            $userSql .= ", created_at) 
                        VALUES (:line_id, 'teacher', :title, :first_name, :last_name, 
                               :phone_number, :email";
            
            // เพิ่มค่า is_active หากมีคอลัมน์
            if ($hasIsActiveColumn) {
                $userSql .= ", :is_active";
            }
            
            $userSql .= ", NOW())";
            
            $stmt = $this->db->prepare($userSql);
            
            $line_id = !empty($data['line_id']) ? $data['line_id'] : 'temp_'.uniqid();
            
            $stmt->bindValue(':line_id', $line_id);
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':phone_number', $data['phone_number']);
            $stmt->bindValue(':email', $data['email']);
            
            if ($hasIsActiveColumn) {
                $is_active = isset($data['status']) && $data['status'] === 'active' ? 1 : 0;
                $stmt->bindValue(':is_active', $is_active);
            }
            
            $stmt->execute();
            
            $user_id = $this->db->lastInsertId();
            
            // เพิ่มข้อมูลในตาราง teachers
            $stmt = $this->db->prepare("INSERT INTO teachers (user_id, title, national_id, 
                                       department, position, first_name, last_name, created_at) 
                                       VALUES (:user_id, :title, :national_id, :department, 
                                       :position, :first_name, :last_name, NOW())");
            
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':national_id', $data['national_id']);
            $stmt->bindValue(':department', $data['department']);
            $stmt->bindValue(':position', $data['position']);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->execute();
            
            $teacher_id = $this->db->lastInsertId();
            
            // หากระบุระดับชั้นที่เป็นที่ปรึกษา
            if (!empty($data['class_id'])) {
                $stmt = $this->db->prepare("INSERT INTO class_advisors (class_id, teacher_id, is_primary) 
                                           VALUES (:class_id, :teacher_id, :is_primary)");
                $stmt->bindValue(':class_id', $data['class_id']);
                $stmt->bindValue(':teacher_id', $teacher_id);
                $stmt->bindValue(':is_primary', !empty($data['is_primary']) ? 1 : 0);
                $stmt->execute();
            }
            
            $this->db->commit();
            return $teacher_id;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * อัปเดตข้อมูลครู
     * 
     * @param int $teacher_id รหัสครู
     * @param array $data ข้อมูลที่ต้องการอัปเดต
     * @return bool ผลการอัปเดต
     */
    public function updateTeacher($teacher_id, $data) {
        try {
            $this->db->beginTransaction();
            
            // ดึงข้อมูล user_id ของครู
            $stmt = $this->db->prepare("SELECT user_id FROM teachers WHERE teacher_id = :teacher_id");
            $stmt->bindValue(':teacher_id', $teacher_id);
            $stmt->execute();
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new Exception("ไม่พบข้อมูลครูที่ต้องการแก้ไข");
            }
            
            // ตรวจสอบว่าตาราง users มีคอลัมน์ is_active หรือไม่
            $columnsStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            $hasIsActiveColumn = $columnsStmt->rowCount() > 0;
            
            // สร้าง SQL สำหรับอัปเดตข้อมูลในตาราง users
            $userSql = "UPDATE users SET 
                       title = :title, 
                       first_name = :first_name, 
                       last_name = :last_name, 
                       phone_number = :phone_number, 
                       email = :email";
            
            // เพิ่มคอลัมน์ is_active หากมี
            if ($hasIsActiveColumn) {
                $userSql .= ", is_active = :is_active";
            }
            
            $userSql .= ", updated_at = NOW()
                          WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($userSql);
            
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':phone_number', $data['phone_number']);
            $stmt->bindValue(':email', $data['email']);
            
            if ($hasIsActiveColumn) {
                $is_active = isset($data['status']) && $data['status'] === 'active' ? 1 : 0;
                $stmt->bindValue(':is_active', $is_active);
            }
            
            $stmt->bindValue(':user_id', $teacher['user_id']);
            $stmt->execute();
            
            // อัปเดตข้อมูลใน teachers
            $stmt = $this->db->prepare("UPDATE teachers SET 
                                       title = :title, 
                                       national_id = :national_id, 
                                       department = :department, 
                                       position = :position, 
                                       first_name = :first_name, 
                                       last_name = :last_name,
                                       updated_at = NOW()
                                       WHERE teacher_id = :teacher_id");
            
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':national_id', $data['national_id']);
            $stmt->bindValue(':department', $data['department']);
            $stmt->bindValue(':position', $data['position']);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':teacher_id', $teacher_id);
            $stmt->execute();
            
            // อัปเดตชั้นเรียนที่เป็นที่ปรึกษา ถ้ามีการเปลี่ยนแปลง
            if (!empty($data['class_id'])) {
                // ลบข้อมูลที่ปรึกษาเดิม (ถ้ามี)
                $stmt = $this->db->prepare("DELETE FROM class_advisors WHERE teacher_id = :teacher_id");
                $stmt->bindValue(':teacher_id', $teacher_id);
                $stmt->execute();
                
                // เพิ่มข้อมูลที่ปรึกษาใหม่
                $stmt = $this->db->prepare("INSERT INTO class_advisors (class_id, teacher_id, is_primary) 
                                           VALUES (:class_id, :teacher_id, :is_primary)");
                $stmt->bindValue(':class_id', $data['class_id']);
                $stmt->bindValue(':teacher_id', $teacher_id);
                $stmt->bindValue(':is_primary', !empty($data['is_primary']) ? 1 : 0);
                $stmt->execute();
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * ลบข้อมูลครู
     * 
     * @param int $teacher_id รหัสครู
     * @return bool ผลการลบ
     */
    public function deleteTeacher($teacher_id) {
        try {
            $this->db->beginTransaction();
            
            // ดึงข้อมูล user_id ของครู
            $stmt = $this->db->prepare("SELECT user_id FROM teachers WHERE teacher_id = :teacher_id");
            $stmt->bindValue(':teacher_id', $teacher_id);
            $stmt->execute();
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new Exception("ไม่พบข้อมูลครูที่ต้องการลบ");
            }
            
            // ลบข้อมูลที่ปรึกษา
            $stmt = $this->db->prepare("DELETE FROM class_advisors WHERE teacher_id = :teacher_id");
            $stmt->bindValue(':teacher_id', $teacher_id);
            $stmt->execute();
            
            // ลบข้อมูลครู
            $stmt = $this->db->prepare("DELETE FROM teachers WHERE teacher_id = :teacher_id");
            $stmt->bindValue(':teacher_id', $teacher_id);
            $stmt->execute();
            
            // ลบข้อมูลผู้ใช้
            $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $teacher['user_id']);
            $stmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * ระงับหรือเปิดใช้งานสิทธิ์ครู
     * 
     * @param int $teacher_id รหัสครู
     * @param bool $active สถานะที่ต้องการเปลี่ยน (true = active, false = inactive)
     * @return bool ผลการเปลี่ยนสถานะ
     */
    public function toggleTeacherStatus($teacher_id, $active = true) {
        try {
            // ดึงข้อมูล user_id ของครู
            $stmt = $this->db->prepare("SELECT user_id FROM teachers WHERE teacher_id = :teacher_id");
            $stmt->bindValue(':teacher_id', $teacher_id);
            $stmt->execute();
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new Exception("ไม่พบข้อมูลครูที่ต้องการเปลี่ยนสถานะ");
            }
            
            // ตรวจสอบว่าตาราง users มีคอลัมน์ is_active หรือไม่
            $columnsStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            $hasIsActiveColumn = $columnsStmt->rowCount() > 0;
            
            // ถ้าไม่มีคอลัมน์ is_active ให้เพิ่มเข้าไป
            if (!$hasIsActiveColumn) {
                try {
                    $this->db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER email");
                    
                    // ตั้งค่าเริ่มต้นให้ทุกคนมีสถานะ active
                    $this->db->exec("UPDATE users SET is_active = 1");
                    
                    $hasIsActiveColumn = true;
                } catch (PDOException $e) {
                    // หากไม่สามารถเพิ่มคอลัมน์ได้ ให้ข้ามไป (อาจจะไม่มีสิทธิ์)
                    error_log("ไม่สามารถเพิ่มคอลัมน์ is_active: " . $e->getMessage());
                }
            }
            
            // อัพเดทสถานะเฉพาะเมื่อมีคอลัมน์ is_active
            if ($hasIsActiveColumn) {
                $stmt = $this->db->prepare("UPDATE users SET is_active = :is_active, updated_at = NOW() 
                                           WHERE user_id = :user_id");
                $stmt->bindValue(':is_active', $active ? 1 : 0, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $teacher['user_id']);
                $stmt->execute();
            } else {
                // ถ้าไม่มีคอลัมน์ is_active และไม่สามารถสร้างได้ 
                // อาจจะใช้วิธีอื่นในการจำแนกสถานะ เช่น เพิ่มคอลัมน์ในตาราง teachers
                // หรือทำเครื่องหมายไว้ที่อื่น
                error_log("ไม่สามารถเปลี่ยนสถานะครู: ไม่มีคอลัมน์ is_active");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Database error in toggleTeacherStatus: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * นำเข้าข้อมูลครูจากไฟล์ Excel
     * 
     * @param string $file_path ที่อยู่ของไฟล์
     * @param bool $overwrite อัปเดตข้อมูลเดิมหรือไม่
     * @return array ผลการนำเข้า (success, total, updated, new, failed)
     */
    public function importFromExcel($file_path, $overwrite = false) {
        // ต้องติดตั้ง PhpSpreadsheet ก่อน: composer require phpoffice/phpspreadsheet
        require_once 'vendor/autoload.php';
        
        $result = [
            'success' => false,
            'total' => 0,
            'updated' => 0,
            'new' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // ตัดหัวตารางออก
            array_shift($rows);
            
            $result['total'] = count($rows);
            
            $this->db->beginTransaction();
            
            foreach ($rows as $row) {
                // ตรวจสอบข้อมูลที่จำเป็น
                if (empty($row[0]) || empty($row[2]) || empty($row[3])) {
                    $result['failed']++;
                    $result['errors'][] = "แถวที่มีข้อมูล '$row[2] $row[3]' ไม่มีข้อมูลที่จำเป็น";
                    continue;
                }
                
                $national_id = $row[0];
                $title = $row[1] ?? 'อาจารย์';
                $first_name = $row[2];
                $last_name = $row[3];
                $department = $row[4] ?? 'อื่นๆ';
                $position = $row[5] ?? 'ครู';
                $phone_number = $row[6] ?? '';
                $email = $row[7] ?? '';
                $status = $row[8] ?? 'active';
                
                // ตรวจสอบว่ามีข้อมูลครูนี้อยู่แล้วหรือไม่ (ตรวจสอบจากเลขบัตรประชาชน)
                $stmt = $this->db->prepare("SELECT t.teacher_id, t.user_id FROM teachers t WHERE t.national_id = :national_id");
                $stmt->bindValue(':national_id', $national_id);
                $stmt->execute();
                $existing_teacher = $stmt->fetch();
                
                // หากมีข้อมูลอยู่แล้วและไม่ต้องการอัปเดต
                if ($existing_teacher && !$overwrite) {
                    continue;
                }
                
                $teacher_data = [
                    'title' => $title,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'national_id' => $national_id,
                    'department' => $department,
                    'position' => $position,
                    'phone_number' => $phone_number,
                    'email' => $email,
                    'status' => strtolower($status) === 'active' ? 'active' : 'inactive'
                ];
                
                // อัปเดตหรือเพิ่มข้อมูล
                if ($existing_teacher) {
                    $this->updateTeacher($existing_teacher['teacher_id'], $teacher_data);
                    $result['updated']++;
                } else {
                    $this->addTeacher($teacher_data);
                    $result['new']++;
                }
            }
            
            $this->db->commit();
            $result['success'] = true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * ดึงรายชื่อแผนกทั้งหมด
     * 
     * @return array รายชื่อแผนก
     */
    public function getAllDepartments() {
        $stmt = $this->db->query("SELECT DISTINCT department FROM teachers ORDER BY department");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $departments;
    }
    
    /**
     * ดึงข้อมูลห้องเรียนทั้งหมด
     * 
     * @return array ข้อมูลห้องเรียน
     */
    public function getAllClasses() {
        $stmt = $this->db->query("SELECT c.class_id, c.level, c.department, c.group_number, 
                                  (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id) as student_count
                                  FROM classes c 
                                  ORDER BY c.level, c.department, c.group_number");
        return $stmt->fetchAll();
    }
}
?>
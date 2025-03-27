<?php
/**
 * Teacher.php - คลาสสำหรับจัดการข้อมูลครู
 */

class Teacher {
    private $db;
    
    /**
     * คอนสตรัคเตอร์
     */
    public function __construct() {
        // เชื่อมต่อกับฐานข้อมูล
        $this->db = getDB();
    }
    
    /**
     * ตรวจสอบว่ามีเลขบัตรประชาชนซ้ำกันหรือไม่
     * 
     * @param string $nationalId เลขบัตรประชาชน
     * @param int|null $excludeTeacherId รหัสครูที่ต้องการยกเว้น (กรณีแก้ไขข้อมูล)
     * @return bool true ถ้ามีข้อมูลซ้ำ, false ถ้าไม่มี
     */
    public function isNationalIdDuplicate($nationalId, $excludeTeacherId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM teachers WHERE national_id = :national_id";
            
            // ถ้ามีการระบุ excludeTeacherId ให้ยกเว้นครูคนนั้น
            if ($excludeTeacherId !== null) {
                $sql .= " AND teacher_id != :exclude_teacher_id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':national_id', $nationalId);
            
            if ($excludeTeacherId !== null) {
                $stmt->bindValue(':exclude_teacher_id', $excludeTeacherId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            return $count > 0;
            
        } catch (PDOException $e) {
            error_log("Error in isNationalIdDuplicate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ดึงข้อมูลครูทั้งหมด
     * 
     * @param int $limit จำนวนข้อมูลสูงสุดที่ต้องการ
     * @param int $offset จำนวนข้อมูลที่ต้องการข้าม
     * @param array $filters ตัวกรองข้อมูล (search, department_id, status, line_status)
     * @return array ข้อมูลครูทั้งหมด
     */
    public function getAllTeachers($limit = 100, $offset = 0, $filters = []) {
        try {
            $sql = "SELECT t.*, u.line_id, u.phone_number, u.email, u.first_name, u.last_name, u.role, u.gdpr_consent, d.department_name
                    FROM teachers t
                    LEFT JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN departments d ON t.department_id = d.department_id
                    WHERE 1=1";
            
            $params = [];
            
            // กรองตามคำค้นหา
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR t.national_id LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            // กรองตามแผนก (ใช้ department_id แทน department name)
            if (isset($filters['department_id']) && !empty($filters['department_id'])) {
                $sql .= " AND t.department_id = :department_id";
                $params['department_id'] = $filters['department_id'];
            }
            // กรองตามชื่อแผนก
            else if (isset($filters['department']) && !empty($filters['department'])) {
                $sql .= " AND d.department_name = :department_name";
                $params['department_name'] = $filters['department'];
            }
            
            // กรองตามสถานะการทำงาน
            if (isset($filters['status'])) {
                if ($filters['status'] === 'active') {
                    $sql .= " AND u.role = 'teacher' AND u.gdpr_consent = 1";
                } elseif ($filters['status'] === 'inactive') {
                    $sql .= " AND (u.role != 'teacher' OR u.gdpr_consent = 0)";
                }
            }
            
            // กรองตามสถานะการเชื่อมต่อ Line
            if (isset($filters['line_status'])) {
                if ($filters['line_status'] === 'connected') {
                    $sql .= " AND u.line_id IS NOT NULL AND u.line_id LIKE 'U%'";
                } elseif ($filters['line_status'] === 'not_connected') {
                    $sql .= " AND (u.line_id IS NULL OR u.line_id NOT LIKE 'U%')";
                }
            }
            
            $sql .= " ORDER BY u.first_name ASC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $teachers = $stmt->fetchAll();
            
            // เพิ่มข้อมูลเพิ่มเติม เช่น จำนวนนักเรียนที่ดูแล
            foreach ($teachers as &$teacher) {
                $teacher['students_count'] = $this->getStudentCountByTeacher($teacher['teacher_id']);
                $teacher['is_active'] = (isset($teacher['role']) && $teacher['role'] === 'teacher' && $teacher['gdpr_consent'] == 1);
                $teacher['department'] = $teacher['department_name']; // เพิ่ม alias สำหรับความเข้ากันได้กับโค้ดเดิม
            }
            
            return $teachers;
            
        } catch (PDOException $e) {
            // บันทึกข้อผิดพลาด
            error_log("Error in getAllTeachers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงจำนวนนักเรียนที่ครูดูแลในปีการศึกษาปัจจุบัน
     * 
     * @param int $teacherId รหัสครู
     * @return int จำนวนนักเรียน
     */
    private function getStudentCountByTeacher($teacherId) {
        try {
            $query = "SELECT COUNT(s.student_id) 
                      FROM students s
                      JOIN classes c ON s.current_class_id = c.class_id
                      JOIN class_advisors ca ON c.class_id = ca.class_id
                      WHERE ca.teacher_id = :teacher_id
                      AND s.status = 'กำลังศึกษา'
                      AND c.academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':teacher_id', $teacherId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            // บันทึกข้อผิดพลาด
            error_log("Error in getStudentCountByTeacher: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * ดึงข้อมูลสถิติเกี่ยวกับครู
     * 
     * @return array ข้อมูลสถิติ
     */
    public function getTeacherStats() {
        try {
            // จำนวนครูทั้งหมด
            $totalQuery = "SELECT COUNT(*) FROM teachers";
            $totalStmt = $this->db->prepare($totalQuery);
            $totalStmt->execute();
            $total = $totalStmt->fetchColumn();
            
            // จำนวนห้องเรียนที่มีครูที่ปรึกษา
            $classroomsQuery = "SELECT COUNT(DISTINCT class_id) FROM class_advisors";
            $classroomsStmt = $this->db->prepare($classroomsQuery);
            $classroomsStmt->execute();
            $classrooms = $classroomsStmt->fetchColumn();
            
            // จำนวนนักเรียนทั้งหมด
            $studentsQuery = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
            $studentsStmt = $this->db->prepare($studentsQuery);
            $studentsStmt->execute();
            $students = $studentsStmt->fetchColumn();
            
            // จำนวนครูที่เชื่อมต่อ Line แล้ว
            $lineConnectedQuery = "SELECT COUNT(*) FROM teachers t
                                  JOIN users u ON t.user_id = u.user_id
                                  WHERE u.line_id IS NOT NULL AND u.line_id LIKE 'U%'";
            $lineConnectedStmt = $this->db->prepare($lineConnectedQuery);
            $lineConnectedStmt->execute();
            $lineConnected = $lineConnectedStmt->fetchColumn();
            
            return [
                'total' => $total,
                'classrooms' => $classrooms,
                'students' => $students,
                'line_connected' => $lineConnected
            ];
            
        } catch (PDOException $e) {
            // บันทึกข้อผิดพลาด
            error_log("Error in getTeacherStats: " . $e->getMessage());
            return [
                'total' => 0,
                'classrooms' => 0,
                'students' => 0,
                'line_connected' => 0
            ];
        }
    }
    
    /**
     * ดึงข้อมูลครูตาม ID
     * 
     * @param int $teacherId รหัสครู
     * @return array|null ข้อมูลครู หรือ null ถ้าไม่พบ
     */
    public function getTeacherById($teacherId) {
        try {
            $query = "SELECT t.*, u.line_id, u.phone_number, u.email, u.first_name, u.last_name, u.role, u.gdpr_consent, 
                      d.department_name 
                      FROM teachers t
                      LEFT JOIN users u ON t.user_id = u.user_id
                      LEFT JOIN departments d ON t.department_id = d.department_id 
                      WHERE t.teacher_id = :teacher_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':teacher_id', $teacherId, PDO::PARAM_INT);
            $stmt->execute();
            
            $teacher = $stmt->fetch();
            
            if ($teacher) {
                $teacher['students_count'] = $this->getStudentCountByTeacher($teacher['teacher_id']);
                $teacher['is_active'] = ($teacher['role'] === 'teacher' && $teacher['gdpr_consent'] == 1);
                $teacher['department'] = $teacher['department_name']; // เพิ่ม alias สำหรับความเข้ากันได้กับโค้ดเดิม
                return $teacher;
            }
            
            return null;
            
        } catch (PDOException $e) {
            // บันทึกข้อผิดพลาด
            error_log("Error in getTeacherById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * เพิ่มครูใหม่
     * 
     * @param array $data ข้อมูลครู
     * @return bool สถานะการทำงาน
     */
    public function addTeacher($data) {
        try {
            // ตรวจสอบเลขบัตรประชาชนซ้ำก่อน
            if ($this->isNationalIdDuplicate($data['national_id'])) {
                throw new Exception("เลขบัตรประชาชน {$data['national_id']} มีอยู่ในระบบแล้ว");
            }
            
            // ตรวจสอบว่าข้อมูลมี department_id หรือ department_name
            $department_id = null;
            if (!empty($data['department_id'])) {
                $department_id = $data['department_id'];
            } elseif (!empty($data['department'])) {
                // ค้นหา department_id จาก department_name
                $department_id = $this->getDepartmentIdByName($data['department']);
                
                // หากไม่พบ department ให้ใช้ค่าเริ่มต้น (IT)
                if (!$department_id) {
                    $department_id = $this->getDepartmentIdByName('เทคโนโลยีสารสนเทศ');
                }
            }
            
            $this->db->beginTransaction();
            
            // สร้าง user ใหม่
            $userData = [
                'role' => 'teacher',
                'title' => $data['title'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'] ?? null,
                'email' => $data['email'] ?? null,
                // สร้าง line_id ชั่วคราว (จะเปลี่ยนเมื่อมีการเชื่อมต่อ Line จริง)
                'line_id' => 'temp_' . time() . rand(1000, 9999)
            ];
            
            $userQuery = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent, created_at) 
                          VALUES (:line_id, :role, :title, :first_name, :last_name, :phone_number, :email, 1, NOW())";
            
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindValue(':line_id', $userData['line_id']);
            $userStmt->bindValue(':role', $userData['role']);
            $userStmt->bindValue(':title', $userData['title']);
            $userStmt->bindValue(':first_name', $userData['first_name']);
            $userStmt->bindValue(':last_name', $userData['last_name']);
            $userStmt->bindValue(':phone_number', $userData['phone_number']);
            $userStmt->bindValue(':email', $userData['email']);
            $userStmt->execute();
            
            $userId = $this->db->lastInsertId();
            
            // สร้างข้อมูลครู
            $teacherQuery = "INSERT INTO teachers (user_id, title, national_id, department_id, position, first_name, last_name, created_at) 
                             VALUES (:user_id, :title, :national_id, :department_id, :position, :first_name, :last_name, NOW())";
            
            $teacherStmt = $this->db->prepare($teacherQuery);
            $teacherStmt->bindValue(':user_id', $userId);
            $teacherStmt->bindValue(':title', $data['title']);
            $teacherStmt->bindValue(':national_id', $data['national_id']);
            $teacherStmt->bindValue(':department_id', $department_id);
            $teacherStmt->bindValue(':position', $data['position']);
            $teacherStmt->bindValue(':first_name', $data['first_name']);
            $teacherStmt->bindValue(':last_name', $data['last_name']);
            $teacherStmt->execute();
            
            $teacherId = $this->db->lastInsertId();
            
            // ถ้ามีการระบุชั้นเรียน ให้เพิ่มเป็นครูที่ปรึกษา
            if (!empty($data['class_id'])) {
                $advisorQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary, assigned_date) 
                                VALUES (:class_id, :teacher_id, 1, NOW())";
                
                $advisorStmt = $this->db->prepare($advisorQuery);
                $advisorStmt->bindValue(':class_id', $data['class_id']);
                $advisorStmt->bindValue(':teacher_id', $teacherId);
                $advisorStmt->execute();
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in addTeacher: " . $e->getMessage());
            
            // ตรวจสอบว่าเป็นข้อผิดพลาดเกี่ยวกับ duplicate key หรือไม่
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'national_id') !== false) {
                throw new Exception("เลขบัตรประชาชน {$data['national_id']} มีอยู่ในระบบแล้ว");
            }
            
            throw new Exception("ไม่สามารถเพิ่มข้อมูลครูได้: " . $e->getMessage());
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * อัปเดตข้อมูลครู
     * 
     * @param int $teacherId รหัสครู
     * @param array $data ข้อมูลที่ต้องการอัปเดต
     * @return bool สถานะการทำงาน
     */
    public function updateTeacher($teacherId, $data) {
        try {
            // ตรวจสอบเลขบัตรประชาชนซ้ำก่อน (ยกเว้นตัวเอง)
            if ($this->isNationalIdDuplicate($data['national_id'], $teacherId)) {
                throw new Exception("เลขบัตรประชาชน {$data['national_id']} มีอยู่ในระบบแล้ว");
            }
            
            // ตรวจสอบว่าข้อมูลมี department_id หรือ department_name
            $department_id = null;
            if (!empty($data['department_id'])) {
                $department_id = $data['department_id'];
            } elseif (!empty($data['department'])) {
                // ค้นหา department_id จาก department_name
                $department_id = $this->getDepartmentIdByName($data['department']);
                
                // หากไม่พบ department ให้ใช้ค่าเริ่มต้น
                if (!$department_id) {
                    $department_id = $this->getDepartmentIdByName('เทคโนโลยีสารสนเทศ');
                }
            }
            
            $this->db->beginTransaction();
            
            // ดึงข้อมูลครูปัจจุบัน
            $teacher = $this->getTeacherById($teacherId);
            if (!$teacher) {
                throw new Exception("ไม่พบข้อมูลครูที่ต้องการแก้ไข");
            }
            
            // อัปเดตข้อมูลในตาราง users
            $userQuery = "UPDATE users SET 
                          title = :title,
                          first_name = :first_name,
                          last_name = :last_name,
                          phone_number = :phone_number,
                          email = :email,
                          updated_at = NOW()
                          WHERE user_id = :user_id";
            
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindValue(':title', $data['title']);
            $userStmt->bindValue(':first_name', $data['first_name']);
            $userStmt->bindValue(':last_name', $data['last_name']);
            $userStmt->bindValue(':phone_number', $data['phone_number']);
            $userStmt->bindValue(':email', $data['email']);
            $userStmt->bindValue(':user_id', $teacher['user_id']);
            $userStmt->execute();
            
            // อัปเดตข้อมูลในตาราง teachers
            $teacherQuery = "UPDATE teachers SET 
                             title = :title,
                             national_id = :national_id,
                             department_id = :department_id,
                             position = :position,
                             first_name = :first_name,
                             last_name = :last_name,
                             updated_at = NOW()
                             WHERE teacher_id = :teacher_id";
            
            $teacherStmt = $this->db->prepare($teacherQuery);
            $teacherStmt->bindValue(':title', $data['title']);
            $teacherStmt->bindValue(':national_id', $data['national_id']);
            $teacherStmt->bindValue(':department_id', $department_id);
            $teacherStmt->bindValue(':position', $data['position']);
            $teacherStmt->bindValue(':first_name', $data['first_name']);
            $teacherStmt->bindValue(':last_name', $data['last_name']);
            $teacherStmt->bindValue(':teacher_id', $teacherId);
            $teacherStmt->execute();
            
            // อัปเดตสถานะการใช้งาน
            if (isset($data['status'])) {
                $active = ($data['status'] === 'active');
                
                // อัปเดตสถานะใน users
                $statusQuery = "UPDATE users SET 
                               role = :role,
                               gdpr_consent = :gdpr_consent
                               WHERE user_id = :user_id";
                
                $statusStmt = $this->db->prepare($statusQuery);
                $statusStmt->bindValue(':role', $active ? 'teacher' : 'inactive');
                $statusStmt->bindValue(':gdpr_consent', $active ? 1 : 0);
                $statusStmt->bindValue(':user_id', $teacher['user_id']);
                $statusStmt->execute();
            }
            
            // ถ้ามีการระบุชั้นเรียน
            if (!empty($data['class_id']) && $data['class_id'] != null) {
                // ตรวจสอบว่าเป็นที่ปรึกษาของชั้นนี้อยู่แล้วหรือไม่
                $checkQuery = "SELECT * FROM class_advisors WHERE teacher_id = :teacher_id AND class_id = :class_id";
                $checkStmt = $this->db->prepare($checkQuery);
                $checkStmt->bindValue(':teacher_id', $teacherId);
                $checkStmt->bindValue(':class_id', $data['class_id']);
                $checkStmt->execute();
                
                if (!$checkStmt->fetch()) {
                    // ลบการเป็นที่ปรึกษาชั้นเก่า (ถ้ามี)
                    $deleteQuery = "DELETE FROM class_advisors WHERE teacher_id = :teacher_id AND is_primary = 1";
                    $deleteStmt = $this->db->prepare($deleteQuery);
                    $deleteStmt->bindValue(':teacher_id', $teacherId);
                    $deleteStmt->execute();
                    
                    // เพิ่มการเป็นที่ปรึกษาชั้นใหม่
                    $advisorQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary, assigned_date) 
                                    VALUES (:class_id, :teacher_id, 1, NOW())";
                    
                    $advisorStmt = $this->db->prepare($advisorQuery);
                    $advisorStmt->bindValue(':class_id', $data['class_id']);
                    $advisorStmt->bindValue(':teacher_id', $teacherId);
                    $advisorStmt->execute();
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in updateTeacher: " . $e->getMessage());
            
            // ตรวจสอบว่าเป็นข้อผิดพลาดเกี่ยวกับ duplicate key หรือไม่
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'national_id') !== false) {
                throw new Exception("เลขบัตรประชาชน {$data['national_id']} มีอยู่ในระบบแล้ว");
            }
            
            throw new Exception("ไม่สามารถอัปเดตข้อมูลครูได้: " . $e->getMessage());
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * ลบข้อมูลครู
     * 
     * @param int $teacherId รหัสครู
     * @return bool สถานะการทำงาน
     */
    public function deleteTeacher($teacherId) {
        try {
            $this->db->beginTransaction();
            
            // ดึงข้อมูลครูปัจจุบัน
            $teacher = $this->getTeacherById($teacherId);
            if (!$teacher) {
                throw new Exception("ไม่พบข้อมูลครูที่ต้องการลบ");
            }
            
            // ตรวจสอบว่าครูมีนักเรียนในความดูแลหรือไม่
            $studentCount = $this->getStudentCountByTeacher($teacherId);
            if ($studentCount > 0) {
                throw new Exception("ไม่สามารถลบครูที่มีนักเรียนในความดูแลได้ กรุณาย้ายนักเรียนไปยังครูที่ปรึกษาคนอื่น");
            }
            
            // ลบข้อมูลจากตาราง class_advisors
            $advisorQuery = "DELETE FROM class_advisors WHERE teacher_id = :teacher_id";
            $advisorStmt = $this->db->prepare($advisorQuery);
            $advisorStmt->bindValue(':teacher_id', $teacherId);
            $advisorStmt->execute();
            
            // ลบข้อมูลจากตาราง teachers
            $teacherQuery = "DELETE FROM teachers WHERE teacher_id = :teacher_id";
            $teacherStmt = $this->db->prepare($teacherQuery);
            $teacherStmt->bindValue(':teacher_id', $teacherId);
            $teacherStmt->execute();
            
            // ลบข้อมูลจากตาราง users
            $userQuery = "DELETE FROM users WHERE user_id = :user_id";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindValue(':user_id', $teacher['user_id']);
            $userStmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in deleteTeacher: " . $e->getMessage());
            throw new Exception("ไม่สามารถลบข้อมูลครูได้: " . $e->getMessage());
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * เปลี่ยนสถานะการทำงานของครู
     * 
     * @param int $teacherId รหัสครู
     * @param bool $active สถานะที่ต้องการ (true = ปฏิบัติงาน, false = ไม่ปฏิบัติงาน)
     * @return bool สถานะการทำงาน
     */
    public function toggleTeacherStatus($teacherId, $active) {
        try {
            $this->db->beginTransaction();
            
            // ดึงข้อมูลครูปัจจุบัน
            $teacher = $this->getTeacherById($teacherId);
            if (!$teacher) {
                throw new Exception("ไม่พบข้อมูลครูที่ต้องการเปลี่ยนสถานะ");
            }
            
            // อัปเดตสถานะใน users
            $statusQuery = "UPDATE users SET 
                           role = :role,
                           gdpr_consent = :gdpr_consent,
                           updated_at = NOW()
                           WHERE user_id = :user_id";
            
            $statusStmt = $this->db->prepare($statusQuery);
            $statusStmt->bindValue(':role', $active ? 'teacher' : 'inactive');
            $statusStmt->bindValue(':gdpr_consent', $active ? 1 : 0);
            $statusStmt->bindValue(':user_id', $teacher['user_id']);
            $statusStmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in toggleTeacherStatus: " . $e->getMessage());
            throw new Exception("ไม่สามารถเปลี่ยนสถานะครูได้: " . $e->getMessage());
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * ดึงรายชื่อแผนกทั้งหมด
     * 
     * @return array รายชื่อแผนก
     */
    public function getAllDepartments() {
        try {
            $sql = "SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $departments = $stmt->fetchAll();
            
            // แปลงให้เป็น array แบบเดิม (เฉพาะชื่อแผนก)
            $departmentNames = [];
            foreach ($departments as $dept) {
                $departmentNames[] = $dept['department_name'];
            }
            
            return $departmentNames;
            
        } catch (PDOException $e) {
            error_log("Error in getAllDepartments: " . $e->getMessage());
            // ส่งค่าเริ่มต้นในกรณีที่เกิดข้อผิดพลาด
            return [
                'ช่างยนต์',
                'ช่างกลโรงงาน',
                'ช่างไฟฟ้ากำลัง',
                'ช่างอิเล็กทรอนิกส์',
                'การบัญชี',
                'เทคโนโลยีสารสนเทศ',
                'การโรงแรม',
                'ช่างเชื่อมโลหะ',
                'บริหาร',
                'สามัญ'
            ];
        }
    }
    
    /**
     * ดึงข้อมูลแผนกทั้งหมดในรูปแบบของอาร์เรย์ที่มี department_id และ department_name
     * 
     * @return array ข้อมูลแผนก
     */
    public function getDepartmentsWithIds() {
        try {
            $sql = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error in getDepartmentsWithIds: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ค้นหา department_id จากชื่อแผนก
     * 
     * @param string $departmentName ชื่อแผนก
     * @return int|null รหัสแผนก หรือ null ถ้าไม่พบ
     */
    public function getDepartmentIdByName($departmentName) {
        try {
            $sql = "SELECT department_id FROM departments WHERE department_name = :name LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $departmentName);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            return $result ? $result['department_id'] : null;
            
        } catch (PDOException $e) {
            error_log("Error in getDepartmentIdByName: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ดึงรายชื่อห้องเรียนทั้งหมด
     * 
     * @return array ข้อมูลห้องเรียน
     */
    public function getAllClasses() {
        try {
            $query = "SELECT c.class_id, c.level, d.department_name, c.group_number, 
                             CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name
                      FROM classes c
                      JOIN departments d ON c.department_id = d.department_id
                      WHERE c.academic_year_id = (
                          SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
                      )
                      AND c.is_active = 1
                      ORDER BY c.level, d.department_name, c.group_number";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            // บันทึกข้อผิดพลาด
            error_log("Error in getAllClasses: " . $e->getMessage());
            return [];
        }
    }
}
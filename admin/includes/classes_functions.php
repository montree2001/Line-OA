<?php
/**
 * classes_functions.php - ฟังก์ชันการจัดการชั้นเรียนและนักเรียน
 */

// เพิ่มชั้นเรียนใหม่
function addClass($data) {
    try {
        $db = getDB();
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['academic_year_id']) || empty($data['level']) || 
            empty($data['department_id']) || empty($data['group_number'])) {
            return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีชั้นเรียนซ้ำหรือไม่
        $checkQuery = "SELECT class_id FROM classes 
                       WHERE academic_year_id = :academic_year_id 
                       AND level = :level 
                       AND department_id = :department_id 
                       AND group_number = :group_number";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'ชั้นเรียนนี้มีอยู่แล้วในระบบ'];
        }
        
        // เพิ่มชั้นเรียนใหม่
        $classroom = isset($data['classroom']) ? $data['classroom'] : null;
        $query = "INSERT INTO classes (academic_year_id, level, department_id, group_number, classroom, is_active) 
                  VALUES (:academic_year_id, :level, :department_id, :group_number, :classroom, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $stmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $stmt->bindParam(':classroom', $classroom, PDO::PARAM_STR);
        $stmt->execute();
        
        $class_id = $db->lastInsertId();
        
        // เพิ่มครูที่ปรึกษา (ถ้ามี)
        if (!empty($data['advisor_id'])) {
            $advisorQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary)
                            VALUES (:class_id, :teacher_id, 1)";
            $advisorStmt = $db->prepare($advisorQuery);
            $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $advisorStmt->bindParam(':teacher_id', $data['advisor_id'], PDO::PARAM_INT);
            $advisorStmt->execute();
        }
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'class_id' => $class_id,
            'academic_year_id' => $data['academic_year_id'],
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'group_number' => $data['group_number']
        ]);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                        VALUES (:admin_id, 'add_class', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'เพิ่มชั้นเรียนสำเร็จ', 'class_id' => $class_id];
    } catch (PDOException $e) {
        error_log("Error adding class: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มชั้นเรียน: ' . $e->getMessage()];
    }
}

// แก้ไขข้อมูลชั้นเรียน
function updateClass($data) {
    try {
        $db = getDB();
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['class_id']) || empty($data['academic_year_id']) || 
            empty($data['level']) || empty($data['department_id']) || empty($data['group_number'])) {
            return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีชั้นเรียนซ้ำหรือไม่ (ยกเว้นชั้นเรียนปัจจุบัน)
        $checkQuery = "SELECT class_id FROM classes 
                       WHERE academic_year_id = :academic_year_id 
                       AND level = :level 
                       AND department_id = :department_id 
                       AND group_number = :group_number
                       AND class_id != :class_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $checkStmt->bindParam(':class_id', $data['class_id'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'ชั้นเรียนนี้มีอยู่แล้วในระบบ'];
        }
        
        // แก้ไขข้อมูลชั้นเรียน
        $classroom = isset($data['classroom']) ? $data['classroom'] : null;
        $query = "UPDATE classes 
                  SET academic_year_id = :academic_year_id,
                      level = :level,
                      department_id = :department_id,
                      group_number = :group_number,
                      classroom = :classroom
                  WHERE class_id = :class_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class_id', $data['class_id'], PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $stmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $stmt->bindParam(':classroom', $classroom, PDO::PARAM_STR);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'class_id' => $data['class_id'],
            'academic_year_id' => $data['academic_year_id'],
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'group_number' => $data['group_number']
        ]);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                        VALUES (:admin_id, 'edit_class', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'แก้ไขข้อมูลชั้นเรียนสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error updating class: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลชั้นเรียน: ' . $e->getMessage()];
    }
}

// ลบชั้นเรียน
function deleteClass($class_id) {
    try {
        $db = getDB();
        
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนนี้หรือไม่
        $checkQuery = "SELECT COUNT(*) AS count FROM students WHERE current_class_id = :class_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'ไม่สามารถลบชั้นเรียนได้ เนื่องจากมีนักเรียนในชั้นเรียนนี้ ' . $result['count'] . ' คน'];
        }
        
        // ลบข้อมูลครูที่ปรึกษาของชั้นเรียนก่อน
        $deleteAdvisorsQuery = "DELETE FROM class_advisors WHERE class_id = :class_id";
        $deleteAdvisorsStmt = $db->prepare($deleteAdvisorsQuery);
        $deleteAdvisorsStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $deleteAdvisorsStmt->execute();
        
        // ลบชั้นเรียน
        $query = "DELETE FROM classes WHERE class_id = :class_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode(['class_id' => $class_id]);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                        VALUES (:admin_id, 'remove_class', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'ลบชั้นเรียนสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error deleting class: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบชั้นเรียน: ' . $e->getMessage()];
    }
}

// จัดการครูที่ปรึกษา
function manageAdvisors($data) {
    try {
        $db = getDB();
        
        if (empty($data['class_id']) || !isset($data['changes']) || !is_array($data['changes'])) {
            return ['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'];
        }
        
        $class_id = $data['class_id'];
        $changes = $data['changes'];
        
        $db->beginTransaction();
        
        foreach ($changes as $change) {
            if (!isset($change['action']) || !isset($change['teacher_id'])) {
                continue;
            }
            
            switch ($change['action']) {
                case 'add':
                    // ตรวจสอบว่าครูนี้เป็นที่ปรึกษาของห้องนี้แล้วหรือไม่
                    $checkQuery = "SELECT COUNT(*) AS count FROM class_advisors 
                                  WHERE class_id = :class_id AND teacher_id = :teacher_id";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $checkStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                    $checkStmt->execute();
                    $result = $checkStmt->fetch();
                    
                    if ($result['count'] == 0) {
                        // ถ้าเพิ่มครูที่ปรึกษาหลัก ต้องยกเลิกครูที่ปรึกษาหลักคนเดิมก่อน
                        if (isset($change['is_primary']) && $change['is_primary']) {
                            $resetQuery = "UPDATE class_advisors SET is_primary = 0 
                                          WHERE class_id = :class_id AND is_primary = 1";
                            $resetStmt = $db->prepare($resetQuery);
                            $resetStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                            $resetStmt->execute();
                        }
                        
                        // เพิ่มครูที่ปรึกษา
                        $addQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary) 
                                    VALUES (:class_id, :teacher_id, :is_primary)";
                        $addStmt = $db->prepare($addQuery);
                        $addStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                        $addStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                        $is_primary = isset($change['is_primary']) && $change['is_primary'] ? 1 : 0;
                        $addStmt->bindParam(':is_primary', $is_primary, PDO::PARAM_INT);
                        $addStmt->execute();
                    }
                    break;
                    
                case 'remove':
                    // ลบครูที่ปรึกษา
                    $removeQuery = "DELETE FROM class_advisors 
                                   WHERE class_id = :class_id AND teacher_id = :teacher_id";
                    $removeStmt = $db->prepare($removeQuery);
                    $removeStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $removeStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                    $removeStmt->execute();
                    break;
                    
                case 'set_primary':
                    // ยกเลิกครูที่ปรึกษาหลักคนเดิม
                    $resetQuery = "UPDATE class_advisors SET is_primary = 0 
                                  WHERE class_id = :class_id AND is_primary = 1";
                    $resetStmt = $db->prepare($resetQuery);
                    $resetStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $resetStmt->execute();
                    
                    // ตั้งครูคนนี้เป็นที่ปรึกษาหลัก
                    $setPrimaryQuery = "UPDATE class_advisors SET is_primary = 1 
                                       WHERE class_id = :class_id AND teacher_id = :teacher_id";
                    $setPrimaryStmt = $db->prepare($setPrimaryQuery);
                    $setPrimaryStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $setPrimaryStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                    $setPrimaryStmt->execute();
                    break;
            }
        }
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'class_id' => $class_id,
            'changes' => $changes
        ]);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'manage_advisors', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        $db->commit();
        
        return ['success' => true, 'message' => 'บันทึกการเปลี่ยนแปลงครูที่ปรึกษาสำเร็จ'];
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error managing advisors: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการจัดการครูที่ปรึกษา: ' . $e->getMessage()];
    }
}

// เลื่อนชั้นนักเรียน
function promoteStudents($data) {
    try {
        $db = getDB();
        
        if (empty($data['from_academic_year_id']) || empty($data['to_academic_year_id'])) {
            return ['success' => false, 'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'];
        }
        
        $from_academic_year_id = $data['from_academic_year_id'];
        $to_academic_year_id = $data['to_academic_year_id'];
        $notes = $data['notes'] ?? '';
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        
        // เรียกใช้ stored procedure สำหรับการเลื่อนชั้น
        $stmt = $db->prepare("CALL promote_students(?, ?, ?, ?)");
        $stmt->bindParam(1, $from_academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $to_academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $admin_id, PDO::PARAM_INT);
        $stmt->bindParam(4, $notes, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $batch_id = $result['batch_id'] ?? null;
        
        if ($batch_id) {
            return ['success' => true, 'message' => 'เลื่อนชั้นนักเรียนสำเร็จ', 'batch_id' => $batch_id];
        } else {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน'];
        }
    } catch (PDOException $e) {
        error_log("Error promoting students: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน: ' . $e->getMessage()];
    }
}

// ดึงข้อมูลชั้นเรียนจากฐานข้อมูล
function getClassesFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                c.class_id,
                c.academic_year_id,
                c.level,
                c.group_number,
                c.department_id,
                d.department_name,
                (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.is_active = 1
            ORDER BY c.level, c.group_number";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $classesResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $classes = [];
        foreach ($classesResult as $row) {
            // ดึงข้อมูลครูที่ปรึกษา
            $advisorQuery = "SELECT 
                    t.teacher_id,
                    CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                    ca.is_primary
                FROM class_advisors ca
                JOIN teachers t ON ca.teacher_id = t.teacher_id
                WHERE ca.class_id = :class_id
                ORDER BY ca.is_primary DESC";
                
            $advisorStmt = $db->prepare($advisorQuery);
            $advisorStmt->bindParam(':class_id', $row['class_id'], PDO::PARAM_INT);
            $advisorStmt->execute();
            $advisorResult = $advisorStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $advisors = [];
            foreach ($advisorResult as $advisor) {
                $advisors[] = [
                    'id' => $advisor['teacher_id'],
                    'name' => $advisor['name'],
                    'is_primary' => (bool)$advisor['is_primary']
                ];
            }
            
            // ดึงอัตราการเข้าแถว
            $attendanceQuery = "SELECT 
                             IFNULL(
                                 (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / 
                                 NULLIF(COUNT(*), 0) * 100
                             ), 0) AS attendance_rate
                             FROM attendance a
                             JOIN students s ON a.student_id = s.student_id
                             WHERE s.current_class_id = :class_id
                             AND a.academic_year_id = :academic_year_id";
            $attendanceStmt = $db->prepare($attendanceQuery);
            $attendanceStmt->bindParam(':class_id', $row['class_id'], PDO::PARAM_INT);
            $attendanceStmt->bindParam(':academic_year_id', $row['academic_year_id'], PDO::PARAM_INT);
            $attendanceStmt->execute();
            $attendanceData = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
            
            $attendanceRate = $attendanceData ? $attendanceData['attendance_rate'] : rand(75, 100);
            
            $classes[] = [
                'class_id' => $row['class_id'],
                'academic_year_id' => $row['academic_year_id'],
                'level' => $row['level'],
                'department' => $row['department_name'],
                'group_number' => $row['group_number'],
                'student_count' => $row['student_count'],
                'attendance_rate' => $attendanceRate,
                'advisors' => $advisors
            ];
        }
        
        return $classes;
    } catch (PDOException $e) {
        error_log("Error fetching classes: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลครูทั้งหมด
function getTeachersFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                t.teacher_id,
                t.title,
                t.first_name,
                t.last_name,
                d.department_name
            FROM 
                teachers t
                LEFT JOIN departments d ON t.department_id = d.department_id
            ORDER BY 
                t.first_name, t.last_name";
                
        $stmt = $db->prepare($query);
        $stmt->execute();
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $teachers;
    } catch (PDOException $e) {
        error_log("Error fetching teachers: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
function getAtRiskStudentCount() {
    try {
        $db = getDB();
        $query = "SELECT COUNT(*) AS count
            FROM risk_students rs
            JOIN students s ON rs.student_id = s.student_id
            JOIN academic_years ay ON rs.academic_year_id = ay.academic_year_id
            WHERE rs.risk_level IN ('high', 'critical')
            AND s.status = 'กำลังศึกษา'
            AND ay.is_active = 1";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    } catch (PDOException $e) {
        error_log("Error fetching at-risk count: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลการเลื่อนชั้น
function getPromotionCounts($academic_year_id) {
    try {
        $db = getDB();
        $query = "SELECT 
                c.level AS current_level,
                COUNT(s.student_id) AS student_count,
                CASE 
                    WHEN c.level = 'ปวช.1' THEN 'ปวช.2'
                    WHEN c.level = 'ปวช.2' THEN 'ปวช.3'
                    WHEN c.level = 'ปวช.3' THEN 'สำเร็จการศึกษา'
                    WHEN c.level = 'ปวส.1' THEN 'ปวส.2'
                    WHEN c.level = 'ปวส.2' THEN 'สำเร็จการศึกษา'
                    ELSE c.level
                END AS new_level
            FROM 
                students s
                JOIN classes c ON s.current_class_id = c.class_id
            WHERE 
                s.status = 'กำลังศึกษา'
                AND c.academic_year_id = :academic_year_id
            GROUP BY 
                current_level, new_level
            ORDER BY 
                c.level";
                
        $stmt = $db->prepare($query);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->execute();
        $promotion_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $promotion_counts;
    } catch (PDOException $e) {
        error_log("Error fetching promotion counts: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลปีการศึกษาจากฐานข้อมูล
function getAcademicYearsFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                academic_year_id,
                year,
                semester,
                is_active,
                start_date,
                end_date,
                required_attendance_days
            FROM academic_years
            ORDER BY year DESC, semester DESC";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลปีการศึกษาปัจจุบัน
        $activeYearQuery = "SELECT academic_year_id, year, semester
            FROM academic_years
            WHERE is_active = 1
            LIMIT 1";
            
        $activeYearStmt = $db->prepare($activeYearQuery);
        $activeYearStmt->execute();
        
        if ($activeYearStmt->rowCount() > 0) {
            $activeYear = $activeYearStmt->fetch(PDO::FETCH_ASSOC);
            
            // ตรวจสอบว่ามีปีการศึกษาถัดไปหรือไม่
            $nextYearQuery = "SELECT academic_year_id, year, semester
                FROM academic_years
                WHERE (year > :year) OR (year = :year AND semester > :semester)
                ORDER BY year ASC, semester ASC
                LIMIT 1";
                
            $nextYearStmt = $db->prepare($nextYearQuery);
            $nextYearStmt->bindParam(':year', $activeYear['year'], PDO::PARAM_INT);
            $nextYearStmt->bindParam(':semester', $activeYear['semester'], PDO::PARAM_INT);
            $nextYearStmt->execute();
            
            $has_new_academic_year = ($nextYearStmt->rowCount() > 0);
            $current_academic_year = $activeYear['year'] . ' ภาคเรียนที่ ' . $activeYear['semester'];
            
            if ($has_new_academic_year) {
                $nextYear = $nextYearStmt->fetch(PDO::FETCH_ASSOC);
                $next_academic_year = $nextYear['year'] . ' ภาคเรียนที่ ' . $nextYear['semester'];
            } else {
                $next_academic_year = '';
            }
        } else {
            $has_new_academic_year = false;
            $current_academic_year = '';
            $next_academic_year = '';
        }
        
        return [
            'academic_years' => $academic_years,
            'has_new_academic_year' => $has_new_academic_year,
            'current_academic_year' => $current_academic_year, 
            'next_academic_year' => $next_academic_year,
            'active_year_id' => $activeYear['academic_year_id'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลรายละเอียดชั้นเรียนพร้อมนักเรียนและครูที่ปรึกษา
function getDetailedClassInfo($class_id) {
    try {
        $db = getDB();
        
        // Get class details
        $classQuery = "SELECT c.*, d.department_name, ay.year, ay.semester, 
                      (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
                      FROM classes c
                      JOIN departments d ON c.department_id = d.department_id
                      JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                      WHERE c.class_id = :class_id";
        $classStmt = $db->prepare($classQuery);
        $classStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $classStmt->execute();
        
        if ($classStmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลชั้นเรียน'];
        }
        
        $class = $classStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get advisors
        $advisorQuery = "SELECT t.teacher_id AS id, 
                        CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                        t.position, ca.is_primary
                        FROM class_advisors ca
                        JOIN teachers t ON ca.teacher_id = t.teacher_id
                        WHERE ca.class_id = :class_id
                        ORDER BY ca.is_primary DESC, t.first_name, t.last_name";
        $advisorStmt = $db->prepare($advisorQuery);
        $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $advisorStmt->execute();
        $advisors = $advisorStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get students
        $studentQuery = "SELECT s.student_id AS id, s.student_code AS code, 
                        CONCAT(s.title, ' ', u.first_name, ' ', u.last_name) AS name,
                        COALESCE(sar.total_attendance_days, 0) AS attendance,
                        (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) AS total,
                        CASE
                            WHEN (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) > 0 
                            THEN (COALESCE(sar.total_attendance_days, 0) / (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) * 100)
                            ELSE 100
                        END AS percent,
                        CASE
                            WHEN (COALESCE(sar.total_attendance_days, 0) / NULLIF((COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)), 0) * 100) > 90 THEN 'ปกติ'
                            WHEN (COALESCE(sar.total_attendance_days, 0) / NULLIF((COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)), 0) * 100) > 75 THEN 'ต้องระวัง'
                            ELSE 'เสี่ยง'
                        END AS status
                        FROM students s
                        JOIN users u ON s.user_id = u.user_id
                        LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = :academic_year_id
                        WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                        ORDER BY u.first_name, u.last_name";
        $studentStmt = $db->prepare($studentQuery);
        $studentStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $studentStmt->bindParam(':academic_year_id', $class['academic_year_id'], PDO::PARAM_INT);
        $studentStmt->execute();
        $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get attendance statistics
        $attendanceQuery = "SELECT 
                          SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) AS present_days,
                          COUNT(*) - SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) AS absent_days,
                          COUNT(*) AS total_days,
                          (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100) AS attendance_rate
                          FROM attendance a
                          JOIN students s ON a.student_id = s.student_id
                          WHERE s.current_class_id = :class_id
                          AND a.academic_year_id = :academic_year_id";
        $attendanceStmt = $db->prepare($attendanceQuery);
        $attendanceStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $attendanceStmt->bindParam(':academic_year_id', $class['academic_year_id'], PDO::PARAM_INT);
        $attendanceStmt->execute();
        $attendanceStats = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get monthly attendance stats
        $monthlyQuery = "SELECT 
                        DATE_FORMAT(a.date, '%m') AS month_num,
                        DATE_FORMAT(a.date, '%b') AS month,
                        SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) AS present,
                        SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) AS absent
                        FROM attendance a
                        JOIN students s ON a.student_id = s.student_id
                        WHERE s.current_class_id = :class_id
                        AND a.academic_year_id = :academic_year_id
                        GROUP BY DATE_FORMAT(a.date, '%m'), DATE_FORMAT(a.date, '%b')
                        ORDER BY month_num";
        $monthlyStmt = $db->prepare($monthlyQuery);
        $monthlyStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $monthlyStmt->bindParam(':academic_year_id', $class['academic_year_id'], PDO::PARAM_INT);
        $monthlyStmt->execute();
        $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no real attendance data, provide sample data for visualization
        if (!$attendanceStats || $attendanceStats['total_days'] == 0) {
            $attendanceStats = [
                'present_days' => 0,
                'absent_days' => 0,
                'total_days' => 0,
                'attendance_rate' => 0
            ];
        }
        
        if (empty($monthlyStats)) {
            $monthlyStats = [
                ['month' => 'ม.ค.', 'present' => 90, 'absent' => 10],
                ['month' => 'ก.พ.', 'present' => 85, 'absent' => 15],
                ['month' => 'มี.ค.', 'present' => 88, 'absent' => 12],
                ['month' => 'เม.ย.', 'present' => 92, 'absent' => 8],
                ['month' => 'พ.ค.', 'present' => 94, 'absent' => 6]
            ];
        }
        
        // Format attendance stats for easy display
        $attendance_stats = [
            'overall_rate' => $attendanceStats['attendance_rate'] ?? 0,
            'present_days' => $attendanceStats['present_days'] ?? 0,
            'absent_days' => $attendanceStats['absent_days'] ?? 0,
            'total_days' => $attendanceStats['total_days'] ?? 0,
            'monthly' => $monthlyStats
        ];
        
        return [
            'success' => true,
            'class' => $class,
            'advisors' => $advisors,
            'students' => $students,
            'attendance_stats' => $attendance_stats
        ];
    } catch (PDOException $e) {
        error_log("Error getting detailed class info: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลรายละเอียดชั้นเรียน'];
    }
}

// ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
function getClassAdvisors($class_id) {
    try {
        $db = getDB();
        
        // Get class info
        $classQuery = "SELECT c.class_id, c.level, c.group_number, d.department_name 
                      FROM classes c 
                      JOIN departments d ON c.department_id = d.department_id 
                      WHERE c.class_id = :class_id";
        $classStmt = $db->prepare($classQuery);
        $classStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $classStmt->execute();
        
        if ($classStmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลชั้นเรียน'];
        }
        
        $class = $classStmt->fetch(PDO::FETCH_ASSOC);
        $class_name = $class['level'] . ' กลุ่ม ' . $class['group_number'] . ' ' . $class['department_name'];
        
        // Get advisors
        $advisorQuery = "SELECT t.teacher_id AS id, 
                        CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                        t.position, ca.is_primary
                        FROM class_advisors ca
                        JOIN teachers t ON ca.teacher_id = t.teacher_id
                        WHERE ca.class_id = :class_id
                        ORDER BY ca.is_primary DESC, t.first_name, t.last_name";
        $advisorStmt = $db->prepare($advisorQuery);
        $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $advisorStmt->execute();
        $advisors = $advisorStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'class_name' => $class_name, 'advisors' => $advisors];
    } catch (PDOException $e) {
        error_log("Error getting class advisors: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลครูที่ปรึกษา'];
    }
}

// ดึงข้อมูลรายละเอียดชั้นเรียน
function getClassDetails($class_id) {
    try {
        $db = getDB();
        $query = "SELECT c.*, d.department_name, ay.year, ay.semester 
                 FROM classes c
                 JOIN departments d ON c.department_id = d.department_id
                 JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                 WHERE c.class_id = :class_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get primary advisor if exists
            $advisorQuery = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, t.position 
                            FROM class_advisors ca
                            JOIN teachers t ON ca.teacher_id = t.teacher_id
                            WHERE ca.class_id = :class_id AND ca.is_primary = 1";
            $advisorStmt = $db->prepare($advisorQuery);
            $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $advisorStmt->execute();
            
            $primary_advisor = $advisorStmt->fetch(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'class' => $class, 'primary_advisor' => $primary_advisor];
        } else {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลชั้นเรียน'];
        }
    } catch (PDOException $e) {
        error_log("Error getting class details: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน'];
    }
}

// ดึงสถิติชั้นเรียน
function getClassStatistics() {
    try {
        $db = getDB();
        
        // Get academic years
        $academicYearQuery = "SELECT academic_year_id, year, semester, is_active 
                            FROM academic_years 
                            ORDER BY is_active DESC, year DESC, semester DESC 
                            LIMIT 1";
        $academicYearStmt = $db->prepare($academicYearQuery);
        $academicYearStmt->execute();
        $activeYear = $academicYearStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$activeYear) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษา'];
        }
        
        // Get level statistics
        $levelStatsQuery = "SELECT 
                          c.level, 
                          COUNT(DISTINCT c.class_id) AS class_count,
                          COUNT(DISTINCT s.student_id) AS student_count,
                          IFNULL(AVG(CASE 
                              WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                              THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100)
                              ELSE NULL
                          END), 0) AS avg_attendance_rate
                          FROM classes c
                          LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
                          LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = :academic_year_id
                          WHERE c.academic_year_id = :academic_year_id
                          GROUP BY c.level
                          ORDER BY FIELD(c.level, 'ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2')";
        $levelStatsStmt = $db->prepare($levelStatsQuery);
        $levelStatsStmt->bindParam(':academic_year_id', $activeYear['academic_year_id'], PDO::PARAM_INT);
        $levelStatsStmt->execute();
        $levelStats = $levelStatsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get department statistics
        $deptStatsQuery = "SELECT 
                         d.department_name, 
                         COUNT(DISTINCT c.class_id) AS class_count,
                         COUNT(DISTINCT s.student_id) AS student_count,
                         IFNULL(AVG(CASE 
                             WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                             THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100)
                             ELSE NULL
                         END), 0) AS avg_attendance_rate
                         FROM departments d
                         LEFT JOIN classes c ON d.department_id = c.department_id AND c.academic_year_id = :academic_year_id
                         LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
                         LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = :academic_year_id
                         GROUP BY d.department_name
                         ORDER BY student_count DESC";
        $deptStatsStmt = $db->prepare($deptStatsQuery);
        $deptStatsStmt->bindParam(':academic_year_id', $activeYear['academic_year_id'], PDO::PARAM_INT);
        $deptStatsStmt->execute();
        $deptStats = $deptStatsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get at-risk statistics
        $atRiskQuery = "SELECT 
                       rs.risk_level,
                       COUNT(*) AS student_count
                       FROM risk_students rs
                       JOIN students s ON rs.student_id = s.student_id
                       WHERE rs.academic_year_id = :academic_year_id
                       AND s.status = 'กำลังศึกษา'
                       GROUP BY rs.risk_level
                       ORDER BY FIELD(rs.risk_level, 'low', 'medium', 'high', 'critical')";
        $atRiskStmt = $db->prepare($atRiskQuery);
        $atRiskStmt->bindParam(':academic_year_id', $activeYear['academic_year_id'], PDO::PARAM_INT);
        $atRiskStmt->execute();
        $atRiskStats = $atRiskStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format statistics
        $statistics = [
            'academic_year' => $activeYear['year'] . ' ภาคเรียนที่ ' . $activeYear['semester'],
            'level_stats' => $levelStats,
            'department_stats' => $deptStats,
            'risk_stats' => $atRiskStats
        ];
        
        return ['success' => true, 'statistics' => $statistics];
    } catch (PDOException $e) {
        error_log("Error getting class statistics: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงสถิติชั้นเรียน'];
    }
}

// ดาวน์โหลดรายงานชั้นเรียน
function downloadClassReport($class_id) {
    try {
        // ดึงข้อมูลรายละเอียดชั้นเรียน
        $classDetails = getDetailedClassInfo($class_id);
        
        if (!$classDetails['success']) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน: ' . ($classDetails['message'] ?? 'ไม่ทราบสาเหตุ');
            exit;
        }
        
        $class = $classDetails['class'];
        $advisors = $classDetails['advisors'];
        $students = $classDetails['students'];
        $attendance_stats = $classDetails['attendance_stats'];
        
        // สร้างชื่อไฟล์
        $filename = 'รายงานชั้น_' . $class['level'] . '_กลุ่ม_' . $class['group_number'] . '_' . $class['department_name'] . '.csv';
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create file pointer to output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel Thai compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write header row
        fputcsv($output, [
            'รายงานการเข้าแถวชั้น ' . $class['level'] . ' กลุ่ม ' . $class['group_number'] . ' ' . $class['department_name']
        ]);
        fputcsv($output, ['ปีการศึกษา: ' . $class['year'] . ' ภาคเรียนที่ ' . $class['semester']]);
        fputcsv($output, []);
        
        // Write advisors
        fputcsv($output, ['ครูที่ปรึกษา']);
        foreach ($advisors as $advisor) {
            fputcsv($output, [$advisor['name'] . ($advisor['is_primary'] ? ' (หลัก)' : ''), $advisor['position']]);
        }
        fputcsv($output, []);
        
        // Write overall statistics
        fputcsv($output, ['สถิติการเข้าแถวโดยรวม']);
        fputcsv($output, ['จำนวนวันทั้งหมด', $attendance_stats['total_days'] . ' วัน']);
        fputcsv($output, ['จำนวนวันที่เข้าแถว', $attendance_stats['present_days'] . ' วัน']);
        fputcsv($output, ['จำนวนวันที่ขาด', $attendance_stats['absent_days'] . ' วัน']);
        fputcsv($output, ['อัตราการเข้าแถว', number_format($attendance_stats['overall_rate'], 2) . '%']);
        fputcsv($output, []);
        
        // Write monthly statistics
        fputcsv($output, ['สถิติรายเดือน']);
        fputcsv($output, ['เดือน', 'เข้าแถว (วัน)', 'ขาด (วัน)', 'อัตราการเข้าแถว (%)']);
        foreach ($attendance_stats['monthly'] as $month) {
            $total = $month['present'] + $month['absent'];
            $rate = $total > 0 ? ($month['present'] / $total * 100) : 0;
            fputcsv($output, [$month['month'], $month['present'], $month['absent'], number_format($rate, 2)]);
        }
        fputcsv($output, []);
        
        // Write student list
        fputcsv($output, ['รายชื่อนักเรียน']);
        fputcsv($output, ['รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'การเข้าแถว (วัน)', 'ร้อยละ', 'สถานะ']);
        foreach ($students as $student) {
            fputcsv($output, [
                $student['code'],
                $student['name'],
                $student['attendance'] . '/' . $student['total'],
                number_format($student['percent'], 2) . '%',
                $student['status']
            ]);
        }
        
        // Close file pointer
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'เกิดข้อผิดพลาดในการสร้างรายงาน: ' . $e->getMessage();
        exit;
    }
}
?>
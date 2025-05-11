<?php
/**
 * classes_functions.php - ฟังก์ชันสำหรับจัดการชั้นเรียน
 */

/**
 * ดึงข้อมูลชั้นเรียนทั้งหมดจากฐานข้อมูล
 * 
 * @return array|false ข้อมูลชั้นเรียนทั้งหมด หรือ false ถ้าเกิดข้อผิดพลาด
 */
function getClassesFromDB() {
    global $conn;
    if (!isset($conn)) {
        error_log('Database connection is not established.');
        return false;
    }
    
    try {
        error_log('Starting to fetch classes data');
        
        // ดึงข้อมูลชั้นเรียนพื้นฐาน
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.academic_year_id, c.level, c.department_id, 
                c.group_number, c.classroom, c.is_active,
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.is_active = 1
            ORDER BY c.level, c.department_id, c.group_number
        ");
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('Successfully fetched ' . count($classes) . ' classes');
        
        // เพิ่มข้อมูลเพิ่มเติมสำหรับแต่ละชั้นเรียน
        foreach ($classes as &$class) {
            try {
                // ดึงข้อมูลครูที่ปรึกษา
                $stmt = $conn->prepare("
                    SELECT 
                        t.teacher_id as id,
                        CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                        ca.is_primary
                    FROM class_advisors ca
                    JOIN teachers t ON ca.teacher_id = t.teacher_id
                    WHERE ca.class_id = ?
                    ORDER BY ca.is_primary DESC
                ");
                $stmt->execute([$class['class_id']]);
                $class['advisors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // ดึงจำนวนนักเรียน
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count
                    FROM students
                    WHERE current_class_id = ? AND status = 'กำลังศึกษา'
                ");
                $stmt->execute([$class['class_id']]);
                $class['student_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // คำนวณอัตราการเข้าแถว
                $stmt = $conn->prepare("
                    SELECT 
                        SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN a.attendance_status != 'present' THEN 1 ELSE 0 END) as absent_days
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    WHERE s.current_class_id = ? AND a.academic_year_id = ?
                ");
                $stmt->execute([$class['class_id'], $class['academic_year_id']]);
                $attendanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $presentDays = (int)($attendanceResult['present_days'] ?? 0);
                $absentDays = (int)($attendanceResult['absent_days'] ?? 0);
                
                $totalDays = $presentDays + $absentDays;
                if ($totalDays > 0) {
                    $class['attendance_rate'] = ($presentDays / $totalDays) * 100;
                } else {
                    $class['attendance_rate'] = 0;
                }
            } catch (PDOException $e) {
                error_log('Error processing additional data for class ID ' . $class['class_id'] . ': ' . $e->getMessage());
                // กำหนดค่าเริ่มต้น
                $class['advisors'] = [];
                $class['student_count'] = 0;
                $class['attendance_rate'] = 0;
            }
        }
        
        return $classes;
    } catch (PDOException $e) {
        error_log('Database error in getClassesFromDB: ' . $e->getMessage());
        return false;
    }
}

/**
 * ดึงข้อมูลชั้นเรียนเฉพาะ
 * 
 * @param int $classId รหัสชั้นเรียน
 * @return array|false ข้อมูลชั้นเรียน หรือ false ถ้าเกิดข้อผิดพลาด
 */
function getClassDetails($classId) {
    global $conn;
    if (!isset($conn)) {
        error_log('Database connection is not established.');
        return false;
    }
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.academic_year_id, c.level, c.department_id, 
                c.group_number, c.classroom, c.is_active,
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            return false;
        }
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                t.teacher_id as id,
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $class['advisors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงจำนวนนักเรียน
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students
            WHERE current_class_id = ? AND status = 'กำลังศึกษา'
        ");
        $stmt->execute([$classId]);
        $class['student_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $class;
    } catch (PDOException $e) {
        error_log('Database error in getClassDetails: ' . $e->getMessage());
        return false;
    }
}

/**
 * เพิ่มชั้นเรียนใหม่
 * 
 * @param array $data ข้อมูลชั้นเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function addClass($data) {
    global $conn;
    if (!isset($conn)) {
        return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'];
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($data['academic_year_id']) || empty($data['level']) || 
        empty($data['department_id']) || empty($data['group_number'])) {
        return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
    }
    
    $academicYearId = $data['academic_year_id'];
    $level = $data['level'];
    $departmentId = $data['department_id'];
    $groupNumber = $data['group_number'];
    $classroom = !empty($data['classroom']) ? $data['classroom'] : null;
    
    try {
        // ตรวจสอบว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM classes
            WHERE academic_year_id = ? AND level = ? AND department_id = ? AND group_number = ?
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'มีชั้นเรียนนี้อยู่แล้วในระบบ'];
        }
        
        // เพิ่มชั้นเรียนใหม่
        $stmt = $conn->prepare("
            INSERT INTO classes (academic_year_id, level, department_id, group_number, classroom, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber, $classroom]);
        
        $classId = $conn->lastInsertId();
        
       
        return [
            'success' => true,
            'message' => 'เพิ่มชั้นเรียนใหม่เรียบร้อยแล้ว',
            'class_id' => $classId
        ];
    } catch (PDOException $e) {
        error_log('Database error in addClass: ' . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * แก้ไขข้อมูลชั้นเรียน
 * 
 * @param array $data ข้อมูลชั้นเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function updateClass($data) {
    global $conn;
    if (!isset($conn)) {
        return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'];
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($data['class_id']) || empty($data['academic_year_id']) || 
        empty($data['level']) || empty($data['department_id']) || 
        empty($data['group_number'])) {
        return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
    }
    
    $classId = $data['class_id'];
    $academicYearId = $data['academic_year_id'];
    $level = $data['level'];
    $departmentId = $data['department_id'];
    $groupNumber = $data['group_number'];
    $classroom = !empty($data['classroom']) ? $data['classroom'] : null;
    
    try {
        // ตรวจสอบว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่ (ยกเว้นชั้นเรียนที่กำลังแก้ไข)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM classes
            WHERE academic_year_id = ? AND level = ? AND department_id = ? AND group_number = ? AND class_id != ?
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber, $classId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'มีชั้นเรียนนี้อยู่แล้วในระบบ'];
        }
        
        // แก้ไขข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            UPDATE classes
            SET academic_year_id = ?, level = ?, department_id = ?, group_number = ?, classroom = ?
            WHERE class_id = ?
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber, $classroom, $classId]);
        
       
        
        return [
            'success' => true,
            'message' => 'แก้ไขข้อมูลชั้นเรียนเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        error_log('Database error in updateClass: ' . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * ลบชั้นเรียน
 * 
 * @param int $classId รหัสชั้นเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function deleteClass($classId) {
    global $conn;
    if (!isset($conn)) {
        return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'];
    }
    
    try {
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนนี้หรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students
            WHERE current_class_id = ? AND status = 'กำลังศึกษา'
        ");
        $stmt->execute([$classId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return [
                'success' => false, 
                'message' => 'ไม่สามารถลบชั้นเรียนได้ เนื่องจากมีนักเรียนในชั้นเรียนนี้ ' . $result['count'] . ' คน'
            ];
        }
        
        // ดึงข้อมูลชั้นเรียนก่อนลบ
        $stmt = $conn->prepare("
            SELECT c.class_id, c.level, c.group_number, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classInfo) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลชั้นเรียน'];
        }
        
        // ลบข้อมูลครูที่ปรึกษาก่อน
        $stmt = $conn->prepare("DELETE FROM class_advisors WHERE class_id = ?");
        $stmt->execute([$classId]);
        
        // ลบข้อมูลชั้นเรียน
        $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt->execute([$classId]);
        
       
        
        return [
            'success' => true,
            'message' => 'ลบชั้นเรียนเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        error_log('Database error in deleteClass: ' . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
 * 
 * @return int จำนวนนักเรียนที่เสี่ยงตก
 */
function getAtRiskStudentCount() {
    global $conn;
    if (!isset($conn)) {
        return 0;
    }
    
    try {
        // ดึงข้อมูลนักเรียนที่มีความเสี่ยงสูง
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM risk_students
            WHERE risk_level IN ('high', 'critical')
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        error_log('Database error in getAtRiskStudentCount: ' . $e->getMessage());
        return 0;
    }
}

/**
 * ดึงข้อมูลจำนวนนักเรียนสำหรับการเลื่อนชั้น
 * 
 * @param int $academicYearId รหัสปีการศึกษาปัจจุบัน
 * @return array|false ข้อมูลการเลื่อนชั้น หรือ false ถ้าเกิดข้อผิดพลาด
 */
function getPromotionCounts($academicYearId) {
    global $conn;
    if (!isset($conn)) {
        return false;
    }
    
    try {
        $result = [];
        $levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];
        
        foreach ($levels as $level) {
            // ดึงจำนวนนักเรียนตามระดับชั้น
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM students s
                JOIN classes c ON s.current_class_id = c.class_id
                WHERE c.academic_year_id = ? AND c.level = ? AND s.status = 'กำลังศึกษา'
            ");
            $stmt->execute([$academicYearId, $level]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                $newLevel = '';
                
                // กำหนดระดับชั้นใหม่
                switch ($level) {
                    case 'ปวช.1':
                        $newLevel = 'ปวช.2';
                        break;
                    case 'ปวช.2':
                        $newLevel = 'ปวช.3';
                        break;
                    case 'ปวช.3':
                        $newLevel = 'สำเร็จการศึกษา';
                        break;
                    case 'ปวส.1':
                        $newLevel = 'ปวส.2';
                        break;
                    case 'ปวส.2':
                        $newLevel = 'สำเร็จการศึกษา';
                        break;
                }
                
                $result[] = [
                    'current_level' => $level,
                    'new_level' => $newLevel,
                    'student_count' => $count
                ];
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Database error in getPromotionCounts: ' . $e->getMessage());
        return false;
    }
}
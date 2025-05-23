<?php
/**
 * department_functions.php - ฟังก์ชันสำหรับจัดการแผนกวิชา
 */

/**
 * เพิ่มแผนกวิชาใหม่
 * 
 * @param array $data ข้อมูลแผนกวิชา
 * @return array ผลลัพธ์การดำเนินการ
 */
function addDepartment($data) {
    try {
        $db = getDB();
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['department_name'])) {
            return ['success' => false, 'message' => 'กรุณาระบุชื่อแผนกวิชา'];
        }
        
        // สร้างรหัสแผนก (ถ้าไม่มี)
        $department_code = isset($data['department_code']) && !empty($data['department_code']) 
            ? strtoupper($data['department_code']) 
            : generateDepartmentCode($data['department_name']);
        
        // ตรวจสอบว่ามีแผนกซ้ำหรือไม่
        $checkQuery = "SELECT department_id FROM departments 
                      WHERE department_code = :code OR department_name = :name";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':code', $department_code, PDO::PARAM_STR);
        $checkStmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'แผนกวิชานี้มีอยู่แล้วในระบบ'];
        }
        
        // เพิ่มแผนกวิชาใหม่
        $query = "INSERT INTO departments (department_code, department_name, is_active) 
                 VALUES (:code, :name, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $department_code, PDO::PARAM_STR);
        $stmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $stmt->execute();
        
        $department_id = $db->lastInsertId();
        
   
        
        return [
            'success' => true, 
            'message' => 'เพิ่มแผนกวิชาสำเร็จ', 
            'department_id' => $department_id,
            'department_code' => $department_code
        ];
    } catch (PDOException $e) {
        error_log("Error adding department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * แก้ไขข้อมูลแผนกวิชา
 * 
 * @param array $data ข้อมูลแผนกวิชา
 * @return array ผลลัพธ์การดำเนินการ
 */
function updateDepartment($data) {
    try {
        $db = getDB();
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['department_id']) || empty($data['department_name'])) {
            return ['success' => false, 'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีแผนกวิชานี้ในระบบหรือไม่
        $checkQuery = "SELECT department_id FROM departments WHERE department_id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $data['department_id'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'ไม่พบแผนกวิชานี้ในระบบ'];
        }
        
        // ตรวจสอบว่ามีชื่อแผนกวิชาซ้ำหรือไม่ (ยกเว้นแผนกปัจจุบัน)
        $dupeQuery = "SELECT department_id FROM departments 
                     WHERE department_name = :name AND department_id != :id";
        $dupeStmt = $db->prepare($dupeQuery);
        $dupeStmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $dupeStmt->bindParam(':id', $data['department_id'], PDO::PARAM_INT);
        $dupeStmt->execute();
        
        if ($dupeStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'มีแผนกวิชาชื่อนี้อยู่แล้วในระบบ'];
        }
        
        // แก้ไขข้อมูลแผนกวิชา
        $query = "UPDATE departments SET department_name = :name WHERE department_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $data['department_id'], PDO::PARAM_INT);
        $stmt->execute();
        
     
        
        return ['success' => true, 'message' => 'แก้ไขข้อมูลแผนกวิชาสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error updating department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * ลบแผนกวิชา
 * 
 * @param int $department_id รหัสแผนกวิชา
 * @return array ผลลัพธ์การดำเนินการ
 */
function deleteDepartment($department_id) {
    try {
        $db = getDB();
        
        // ตรวจสอบว่ามีชั้นเรียนที่ใช้แผนกนี้อยู่หรือไม่
        $checkClassesQuery = "SELECT COUNT(*) AS count FROM classes WHERE department_id = :id";
        $checkClassesStmt = $db->prepare($checkClassesQuery);
        $checkClassesStmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $checkClassesStmt->execute();
        $classesCount = $checkClassesStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($classesCount > 0) {
            return ['success' => false, 'message' => 'ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีชั้นเรียนที่ใช้แผนกนี้อยู่ ' . $classesCount . ' ชั้นเรียน'];
        }
        
        // ตรวจสอบว่ามีครูที่อยู่ในแผนกนี้หรือไม่
        $checkTeachersQuery = "SELECT COUNT(*) AS count FROM teachers WHERE department_id = :id";
        $checkTeachersStmt = $db->prepare($checkTeachersQuery);
        $checkTeachersStmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $checkTeachersStmt->execute();
        $teachersCount = $checkTeachersStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($teachersCount > 0) {
            return ['success' => false, 'message' => 'ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีครูที่อยู่ในแผนกนี้ ' . $teachersCount . ' คน'];
        }
        
        // ดึงข้อมูลแผนกวิชาก่อนลบ
        $getDeptQuery = "SELECT department_code, department_name FROM departments WHERE department_id = :id";
        $getDeptStmt = $db->prepare($getDeptQuery);
        $getDeptStmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $getDeptStmt->execute();
        $departmentInfo = $getDeptStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$departmentInfo) {
            return ['success' => false, 'message' => 'ไม่พบแผนกวิชานี้ในระบบ'];
        }
        
        // ลบแผนกวิชา
        $query = "DELETE FROM departments WHERE department_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $stmt->execute();
        
        
        
        return ['success' => true, 'message' => 'ลบแผนกวิชาสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error deleting department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลแผนกวิชา
 * 
 * @param int $department_id รหัสแผนกวิชา
 * @return array ข้อมูลแผนกวิชา
 */
function getDepartmentDetails($department_id) {
    try {
        $db = getDB();
        $query = "SELECT d.*, 
                 (SELECT COUNT(*) FROM classes c WHERE c.department_id = d.department_id) AS class_count,
                 (SELECT COUNT(*) FROM students s 
                  JOIN classes c ON s.current_class_id = c.class_id 
                  WHERE c.department_id = d.department_id AND s.status = 'กำลังศึกษา') AS student_count,
                 (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.department_id) AS teacher_count
                 FROM departments d
                 WHERE d.department_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'department' => $department];
        } else {
            return ['success' => false, 'message' => 'ไม่พบแผนกวิชานี้ในระบบ'];
        }
    } catch (PDOException $e) {
        error_log("Error getting department details: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลแผนกวิชาทั้งหมด
 * 
 * @param bool $active_only ดึงเฉพาะแผนกวิชาที่ใช้งานอยู่
 * @return array ข้อมูลแผนกวิชาทั้งหมด
 */
function getAllDepartments($active_only = true) {
    try {
        $db = getDB();
        $query = "SELECT d.*, 
                 (SELECT COUNT(*) FROM classes c WHERE c.department_id = d.department_id) AS class_count,
                 (SELECT COUNT(*) FROM students s 
                  JOIN classes c ON s.current_class_id = c.class_id 
                  WHERE c.department_id = d.department_id AND s.status = 'กำลังศึกษา') AS student_count,
                 (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.department_id) AS teacher_count
                 FROM departments d";
        
        if ($active_only) {
            $query .= " WHERE d.is_active = 1";
        }
        
        $query .= " ORDER BY d.department_name";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'departments' => $departments];
    } catch (PDOException $e) {
        error_log("Error getting departments: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * สร้างรหัสแผนกวิชาจากชื่อแผนก
 * 
 * @param string $department_name ชื่อแผนกวิชา
 * @return string รหัสแผนกวิชา
 */
function generateDepartmentCode($department_name) {
    try {
        // ดึงเฉพาะตัวอักษรภาษาอังกฤษ
        preg_match_all('/[a-zA-Z]+/', $department_name, $matches);
        $words = $matches[0];
        
        if (count($words) > 0) {
            // ถ้ามีคำภาษาอังกฤษ ใช้อักษรตัวแรกของแต่ละคำ
            $code = '';
            foreach ($words as $word) {
                $code .= strtoupper(substr($word, 0, 1));
            }
            
            // ถ้าสั้นเกินไป ให้เอาตัวที่ 2 ของคำแรกมาเพิ่ม
            if (strlen($code) < 3 && isset($words[0]) && strlen($words[0]) > 1) {
                $code .= strtoupper(substr($words[0], 1, 1));
            }
        } else {
            // ถ้าไม่มีคำภาษาอังกฤษ ใช้ตัวอักษร 4 ตัวแรกของชื่อแผนก
            $code = strtoupper(substr($department_name, 0, 4));
        }
        
        // ถ้ายังสั้นเกินไป ให้เติม X
        while (strlen($code) < 3) {
            $code .= 'X';
        }
        
        // ตัดให้ไม่เกิน 10 ตัวอักษร
        $code = substr($code, 0, 10);
        
        // ตรวจสอบว่ามีรหัสซ้ำในระบบหรือไม่
        $db = getDB();
        $found_unique = false;
        $suffix = '';
        $attempt = 0;
        
        while (!$found_unique && $attempt < 100) {
            $test_code = $code . $suffix;
            
            $query = "SELECT COUNT(*) AS count FROM departments WHERE department_code = :code";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':code', $test_code, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                $found_unique = true;
                $code = $test_code;
            } else {
                $attempt++;
                $suffix = $attempt;
            }
        }
        
        return $code;
    } catch (Exception $e) {
        error_log("Error generating department code: " . $e->getMessage());
        // หากเกิดข้อผิดพลาด ให้สร้างรหัสแบบสุ่ม
        return 'DEPT' . rand(100, 999);
    }
}
function getDepartmentsFromDB() {
    try {
        $conn = getDB();
        if ($conn === null) {
            error_log('Database connection is not established.');
            return false;
        }
        
        // บันทึก log การเริ่มดึงข้อมูล
        error_log('Starting to fetch departments data');
        
        $query = "SELECT 
                d.department_id, 
                d.department_code, 
                d.department_name,
                d.is_active,
                (SELECT COUNT(*) FROM classes c WHERE c.department_id = d.department_id) as class_count,
                (SELECT COUNT(*) FROM students s 
                 JOIN classes c ON s.current_class_id = c.class_id 
                 WHERE c.department_id = d.department_id AND s.status = 'กำลังศึกษา') as student_count,
                (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.department_id) as teacher_count
                FROM departments d
                ORDER BY d.department_name";
                
        $stmt = $conn->query($query);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('Successfully fetched ' . count($departments) . ' departments');
        return $departments;
    } catch (PDOException $e) {
        error_log('Error fetching departments: ' . $e->getMessage());
        error_log('SQL Query: ' . $query ?? 'No query available');
        return false;
    }
}



/**
 * สำหรับตัวอย่างการทดสอบ
 * @return array ข้อมูลแผนกวิชาตัวอย่าง
 */
function getSampleDepartments() {
    return [
        [
            'department_id' => '1',
            'department_code' => 'IT',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'is_active' => 1,
            'class_count' => 3,
            'student_count' => 80,
            'teacher_count' => 5
        ],
        [
            'department_id' => '2',
            'department_code' => 'ACC',
            'department_name' => 'การบัญชี',
            'is_active' => 1,
            'class_count' => 2,
            'student_count' => 60,
            'teacher_count' => 4
        ],
        [
            'department_id' => '3',
            'department_code' => 'AUTO',
            'department_name' => 'ช่างยนต์',
            'is_active' => 1,
            'class_count' => 3,
            'student_count' => 90,
            'teacher_count' => 6
        ],
        [
            'department_id' => '4',
            'department_code' => 'ELEC',
            'department_name' => 'ช่างไฟฟ้ากำลัง',
            'is_active' => 1,
            'class_count' => 2,
            'student_count' => 70,
            'teacher_count' => 4
        ],
        [
            'department_id' => '5',
            'department_code' => 'MECH',
            'department_name' => 'ช่างกลโรงงาน',
            'is_active' => 1,
            'class_count' => 2,
            'student_count' => 65,
            'teacher_count' => 3
        ],
        [
            'department_id' => '6',
            'department_code' => 'HOTEL',
            'department_name' => 'การโรงแรม',
            'is_active' => 1,
            'class_count' => 1,
            'student_count' => 45,
            'teacher_count' => 2
        ],
        [
            'department_id' => '7',
            'department_code' => 'GEN',
            'department_name' => 'สามัญ',
            'is_active' => 1,
            'class_count' => 0,
            'student_count' => 0,
            'teacher_count' => 8
        ]
    ];
}









?>
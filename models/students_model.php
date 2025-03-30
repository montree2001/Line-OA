<?php
/**
 * students_model.php - โมเดลสำหรับจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

/**
 * ดึงข้อมูลนักเรียนทั้งหมด
 * 
 * @param array $filters ตัวกรองข้อมูล
 * @return array ข้อมูลนักเรียน
 */
function getAllStudents($filters = []) {
    try {
        $conn = getDB();
        
        // สร้างเงื่อนไขการค้นหา
        $where_conditions = [];
        $params = [];
        
        if (isset($filters['name']) && !empty($filters['name'])) {
            $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search_name = '%' . $filters['name'] . '%';
            $params[] = $search_name;
            $params[] = $search_name;
        }
        
        if (isset($filters['student_code']) && !empty($filters['student_code'])) {
            $where_conditions[] = "s.student_code LIKE ?";
            $params[] = '%' . $filters['student_code'] . '%';
        }
        
        if (isset($filters['level']) && !empty($filters['level'])) {
            $where_conditions[] = "c.level = ?";
            $params[] = $filters['level'];
        }
        
        if (isset($filters['group_number']) && !empty($filters['group_number'])) {
            $where_conditions[] = "c.group_number = ?";
            $params[] = $filters['group_number'];
        }
        
        if (isset($filters['department_id']) && !empty($filters['department_id'])) {
            $where_conditions[] = "c.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where_conditions[] = "s.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['line_status']) && !empty($filters['line_status'])) {
            if ($filters['line_status'] === 'connected') {
                $where_conditions[] = "u.line_id IS NOT NULL AND u.line_id != ''";
            } else if ($filters['line_status'] === 'not_connected') {
                $where_conditions[] = "(u.line_id IS NULL OR u.line_id = '')";
            }
        }
        
        // สร้าง SQL condition
        $sql_condition = "";
        if (!empty($where_conditions)) {
            $sql_condition = " WHERE " . implode(" AND ", $where_conditions);
        }
        
        // ดึงข้อมูลนักเรียน
        $query = "SELECT DISTINCT s.student_id, s.student_code, s.status, 
                 u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email, u.user_id,
                 c.level, c.group_number, c.class_id, c.academic_year_id,
                 d.department_name, d.department_id,
                 (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                  FROM class_advisors ca 
                  JOIN teachers t ON ca.teacher_id = t.teacher_id 
                  WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                  LIMIT 1) as advisor_name,
                 IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1), 0) as attendance_days,
                 IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0), 0) as absence_days
                 FROM students s
                 JOIN users u ON s.user_id = u.user_id
                 LEFT JOIN classes c ON s.current_class_id = c.class_id
                 LEFT JOIN departments d ON c.department_id = d.department_id
                 $sql_condition
                 ORDER BY s.student_code";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $conn->query($query);
        }
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // เติมข้อมูลเพิ่มเติม
        foreach ($students as &$student) {
            // สร้างชื่อชั้นเรียน
            $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
            
            // คำนวณอัตราการเข้าแถว
            $total_days = $student['attendance_days'] + $student['absence_days'];
            if ($total_days > 0) {
                $student['attendance_rate'] = ($student['attendance_days'] / $total_days) * 100;
            } else {
                $student['attendance_rate'] = 100; // ถ้ายังไม่มีข้อมูลให้เป็น 100%
            }
            
            // กำหนดสถานะการเข้าแถว
            if ($student['attendance_rate'] < 60) {
                $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
            } elseif ($student['attendance_rate'] < 75) {
                $student['attendance_status'] = 'ต้องระวัง';
            } else {
                $student['attendance_status'] = 'ปกติ';
            }
            
            // ตรวจสอบการเชื่อมต่อกับ LINE
            $student['line_connected'] = !empty($student['line_id']);
        }
        
        // กรองตามสถานะการเข้าแถว (ถ้ามี)
        if (isset($filters['attendance_status']) && !empty($filters['attendance_status'])) {
            $attendance_status = $filters['attendance_status'];
            $students = array_filter($students, function($student) use ($attendance_status) {
                return $student['attendance_status'] === $attendance_status;
            });
            // Reset array keys
            $students = array_values($students);
        }
        
        return $students;
    } catch (PDOException $e) {
        error_log("Error fetching students: " . $e->getMessage());
        return [];
    }
}

/**
 * ดึงข้อมูลนักเรียนตาม ID
 * 
 * @param int $student_id รหัสนักเรียน
 * @return array|bool ข้อมูลนักเรียนหรือ false ถ้าไม่พบ
 */
function getStudentById($student_id) {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลนักเรียน
        $query = "SELECT s.student_id, s.student_code, s.current_class_id as class_id, s.status, 
                  u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email, u.user_id,
                  c.level, c.group_number, c.academic_year_id,
                  d.department_name, d.department_id,
                  (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                   FROM class_advisors ca 
                   JOIN teachers t ON ca.teacher_id = t.teacher_id 
                   WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                   LIMIT 1) as advisor_name,
                  IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1), 0) as attendance_days,
                  IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0), 0) as absence_days
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN classes c ON s.current_class_id = c.class_id
                  LEFT JOIN departments d ON c.department_id = d.department_id
                  WHERE s.student_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            return false;
        }
        
        // เพิ่มข้อมูลเพิ่มเติม
        $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
        
        // คำนวณอัตราการเข้าแถว
        $total_days = $student['attendance_days'] + $student['absence_days'];
        if ($total_days > 0) {
            $student['attendance_rate'] = ($student['attendance_days'] / $total_days) * 100;
        } else {
            $student['attendance_rate'] = 100; // ถ้ายังไม่มีข้อมูลให้เป็น 100%
        }
        
        // กำหนดสถานะการเข้าแถว
        if ($student['attendance_rate'] < 60) {
            $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
        } elseif ($student['attendance_rate'] < 75) {
            $student['attendance_status'] = 'ต้องระวัง';
        } else {
            $student['attendance_status'] = 'ปกติ';
        }
        
        // ตรวจสอบการเชื่อมต่อกับ LINE
        $student['line_connected'] = !empty($student['line_id']);
        
        return $student;
    } catch (PDOException $e) {
        error_log("Error fetching student: " . $e->getMessage());
        return false;
    }
}

/**
 * เพิ่มนักเรียนใหม่
 * 
 * @param array $data ข้อมูลนักเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function addStudent($data) {
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีรหัสนักเรียนซ้ำหรือไม่
        $checkQuery = "SELECT student_id FROM students WHERE student_code = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([trim($data['student_code'])]);
        
        if ($checkStmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'รหัสนักเรียนนี้มีอยู่ในระบบแล้ว'
            ];
        }
        
        // ใช้ line_id ชั่วคราวที่ไม่ซ้ำกัน แทนค่าว่าง
        $tempLineId = 'TEMP_' . $data['student_code'] . '_' . time() . '_' . substr(md5(rand()), 0, 6);
        
        // 1. เพิ่มข้อมูลในตาราง users ก่อน
        $userQuery = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                     VALUES (?, 'student', ?, ?, ?, ?, ?, 1)";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([
            $tempLineId, // ใช้ line_id ชั่วคราวที่ไม่ซ้ำกัน
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['phone_number'] ?? '',
            $data['email'] ?? ''
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // 2. เพิ่มข้อมูลนักเรียน
        $studentQuery = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                        VALUES (?, ?, ?, ?, ?)";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([
            $user_id,
            trim($data['student_code']),
            $data['title'],
            !empty($data['class_id']) ? $data['class_id'] : null,
            $data['status'] ?? 'กำลังศึกษา'
        ]);
        
        $student_id = $conn->lastInsertId();
        
        // 3. เพิ่มข้อมูลในตาราง student_academic_records ถ้ามีการเลือกชั้นเรียน
        if (!empty($data['class_id'])) {
            // ดึงข้อมูล academic_year_id จากชั้นเรียน
            $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
            $yearStmt = $conn->prepare($yearQuery);
            $yearStmt->execute([$data['class_id']]);
            $academic_year_id = $yearStmt->fetchColumn();
            
            if ($academic_year_id) {
                $recordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                              VALUES (?, ?, ?)";
                $recordStmt = $conn->prepare($recordQuery);
                $recordStmt->execute([$student_id, $academic_year_id, $data['class_id']]);
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว',
            'student_id' => $student_id
        ];
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        
        error_log("Error adding student: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลนักเรียน: ' . $e->getMessage()
        ];
    }
}

/**
 * อัปเดตข้อมูลนักเรียน
 * 
 * @param array $data ข้อมูลนักเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function updateStudent($data) {
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีนักเรียนคนนี้ในระบบหรือไม่
        $checkQuery = "SELECT user_id, current_class_id FROM students WHERE student_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$data['student_id']]);
        $studentData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$studentData) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียนในระบบ'
            ];
        }
        
        // ตรวจสอบว่ามีรหัสนักเรียนซ้ำหรือไม่ (ยกเว้นนักเรียนคนนี้)
        $dupeQuery = "SELECT student_id FROM students 
                     WHERE student_code = ? AND student_id != ?";
        $dupeStmt = $conn->prepare($dupeQuery);
        $dupeStmt->execute([
            trim($data['student_code']),
            $data['student_id']
        ]);
        
        if ($dupeStmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'รหัสนักเรียนนี้มีอยู่ในระบบแล้ว'
            ];
        }
        
        // อัปเดตข้อมูลในตาราง users
        $userQuery = "UPDATE users 
                     SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE user_id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['phone_number'] ?? '',
            $data['email'] ?? '',
            $studentData['user_id']
        ]);
        
        // อัปเดตข้อมูลในตาราง students
        $studentQuery = "UPDATE students 
                        SET student_code = ?, title = ?, current_class_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE student_id = ?";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([
            trim($data['student_code']),
            $data['title'],
            $data['class_id'] ?? null,
            $data['status'] ?? 'กำลังศึกษา',
            $data['student_id']
        ]);
        
        // ถ้ามีการเปลี่ยนชั้นเรียน
        if (!empty($data['class_id']) && $data['class_id'] != $studentData['current_class_id']) {
            // ดึงข้อมูล academic_year_id จากชั้นเรียนใหม่
            $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
            $yearStmt = $conn->prepare($yearQuery);
            $yearStmt->execute([$data['class_id']]);
            $academic_year_id = $yearStmt->fetchColumn();
            
            if ($academic_year_id) {
                // ตรวจสอบว่ามีประวัติการศึกษาในปีการศึกษานี้หรือไม่
                $recordQuery = "SELECT record_id FROM student_academic_records 
                               WHERE student_id = ? AND academic_year_id = ?";
                $recordStmt = $conn->prepare($recordQuery);
                $recordStmt->execute([
                    $data['student_id'],
                    $academic_year_id
                ]);
                $record_id = $recordStmt->fetchColumn();
                
                if ($record_id) {
                    // อัปเดตประวัติการศึกษาที่มีอยู่แล้ว
                    $updateRecordQuery = "UPDATE student_academic_records 
                                         SET class_id = ?, updated_at = CURRENT_TIMESTAMP
                                         WHERE record_id = ?";
                    $updateRecordStmt = $conn->prepare($updateRecordQuery);
                    $updateRecordStmt->execute([
                        $data['class_id'],
                        $record_id
                    ]);
                } else {
                    // สร้างประวัติการศึกษาใหม่
                    $newRecordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                      VALUES (?, ?, ?)";
                    $newRecordStmt = $conn->prepare($newRecordQuery);
                    $newRecordStmt->execute([
                        $data['student_id'],
                        $academic_year_id,
                        $data['class_id']
                    ]);
                }
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'อัปเดตข้อมูลนักเรียนเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        
        error_log("Error updating student: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลนักเรียน: ' . $e->getMessage()
        ];
    }
}

/**
 * ลบข้อมูลนักเรียน
 * 
 * @param int $student_id รหัสนักเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function deleteStudent($student_id) {
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีนักเรียนคนนี้ในระบบหรือไม่
        $checkQuery = "SELECT user_id FROM students WHERE student_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$student_id]);
        $user_id = $checkStmt->fetchColumn();
        
        if (!$user_id) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียนในระบบ'
            ];
        }
        
        // ลบข้อมูลในตารางที่เกี่ยวข้อง
        $tables = [
            'student_academic_records',
            'attendance',
            'risk_students',
            'qr_codes',
            'parent_student_relation',
            'class_history',
            'notifications'
        ];
        
        foreach ($tables as $table) {
            $deleteQuery = "DELETE FROM $table WHERE student_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->execute([$student_id]);
        }
        
        // ลบข้อมูลในตาราง students
        $studentQuery = "DELETE FROM students WHERE student_id = ?";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([$student_id]);
        
        // ลบข้อมูลในตาราง users
        $userQuery = "DELETE FROM users WHERE user_id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([$user_id]);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'ลบข้อมูลนักเรียนเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        
        error_log("Error deleting student: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน: ' . $e->getMessage()
        ];
    }
}


/**
 * ฟังก์ชันสำหรับนำเข้าข้อมูลนักเรียนจากไฟล์ Excel/CSV
 * ปรับปรุงจากฟังก์ชันเดิมใน models/students_model.php
 * 
 * @param array $file ข้อมูลไฟล์ที่อัปโหลด
 * @param array $options ตัวเลือกการนำเข้า
 * @return array ผลลัพธ์การดำเนินการ
 */
function importStudentsFromExcel($file, $options = []) {
    try {
        // ตรวจสอบไฟล์
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'ไม่มีไฟล์หรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์'
            ];
        }
        
        // ตรวจสอบนามสกุลไฟล์
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return [
                'success' => false,
                'message' => 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) หรือ CSV เท่านั้น'
            ];
        }
        
        // อ่านข้อมูลจากไฟล์ Excel
        require_once 'vendor/autoload.php'; // PhpSpreadsheet
        
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
        
        // ตั้งค่า reader สำหรับ CSV เพื่อรองรับภาษาไทย
        if ($ext === 'csv') {
            $reader->setInputEncoding('UTF-8');
        }
        
        $spreadsheet = $reader->load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // ข้ามแถวแรก (หัวตาราง) ถ้าเลือกตัวเลือก skip_header
        $startRow = (isset($options['skip_header']) && $options['skip_header']) ? 1 : 0;
        
        // กำหนดชั้นเรียนสำหรับนักเรียนที่นำเข้า
        $class_id = $options['import_class_id'] ?? null;
        
        // สร้างการเชื่อมต่อฐานข้อมูล
        $conn = getDB();
        $conn->beginTransaction();
        
        // ตัวแปรสำหรับนับจำนวนการนำเข้า
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        
        // วนลูปแต่ละแถวในไฟล์
        for ($i = $startRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                $skipped++;
                $errors[] = "แถวที่ " . ($i + 1) . ": ข้อมูลไม่ครบถ้วน";
                continue;
            }
            
            $student_code = trim($row[0]);
            $title = trim($row[1]);
            $firstname = trim($row[2]);
            $lastname = trim($row[3]);
            $phone = isset($row[4]) ? trim($row[4]) : '';
            $email = isset($row[5]) ? trim($row[5]) : '';
            $level = isset($row[6]) ? trim($row[6]) : '';
            $group = isset($row[7]) ? trim($row[7]) : '';
            $department = isset($row[8]) ? trim($row[8]) : '';
            $status = isset($row[9]) ? trim($row[9]) : 'กำลังศึกษา';
            
            // ตรวจสอบค่าที่รองรับ
            if (!in_array($title, ['นาย', 'นางสาว', 'เด็กชาย', 'เด็กหญิง'])) {
                $skipped++;
                $errors[] = "แถวที่ " . ($i + 1) . ": คำนำหน้าไม่ถูกต้อง (รองรับเฉพาะ นาย, นางสาว, เด็กชาย, เด็กหญิง)";
                continue;
            }
            
            if (!empty($status) && !in_array($status, ['กำลังศึกษา', 'พักการเรียน', 'พ้นสภาพ', 'สำเร็จการศึกษา'])) {
                $skipped++;
                $errors[] = "แถวที่ " . ($i + 1) . ": สถานะการศึกษาไม่ถูกต้อง";
                continue;
            }
            
            try {
                // ตรวจสอบว่ามีนักเรียนคนนี้ในระบบหรือไม่
                $checkQuery = "SELECT s.student_id, s.user_id FROM students s WHERE s.student_code = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->execute([$student_code]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($checkResult && isset($options['update_existing']) && $options['update_existing']) {
                    // อัปเดตข้อมูลนักเรียนที่มีอยู่แล้ว
                    $student_id = $checkResult['student_id'];
                    $user_id = $checkResult['user_id'];
                    
                    // อัปเดตข้อมูลในตาราง users
                    $userQuery = "UPDATE users 
                               SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = CURRENT_TIMESTAMP
                               WHERE user_id = ?";
                    $userStmt = $conn->prepare($userQuery);
                    $userStmt->execute([
                        $title,
                        $firstname,
                        $lastname,
                        $phone,
                        $email,
                        $user_id
                    ]);
                    
                    // อัปเดตข้อมูลในตาราง students
                    $studentQuery = "UPDATE students 
                                  SET title = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                                  WHERE student_id = ?";
                    $studentStmt = $conn->prepare($studentQuery);
                    $studentStmt->execute([
                        $title,
                        $status,
                        $student_id
                    ]);
                    
                    // อัปเดตชั้นเรียนถ้ามีการระบุ
                    $current_class_id = null;
                    
                    if ($class_id) {
                        // ใช้ชั้นเรียนที่ระบุไว้ตอนนำเข้า
                        $current_class_id = $class_id;
                    } else if ($level && $group && $department) {
                        // ค้นหาชั้นเรียนตามระดับ กลุ่ม และแผนก
                        $findClassQuery = "SELECT c.class_id FROM classes c 
                                        JOIN departments d ON c.department_id = d.department_id
                                        JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                                        WHERE c.level = ? AND c.group_number = ? AND d.department_name = ? AND ay.is_active = 1";
                        $findClassStmt = $conn->prepare($findClassQuery);
                        $findClassStmt->execute([$level, $group, $department]);
                        $current_class_id = $findClassStmt->fetchColumn();
                    }
                    
                    if ($current_class_id) {
                        // อัปเดตชั้นเรียน
                        $updateClassQuery = "UPDATE students SET current_class_id = ? WHERE student_id = ?";
                        $updateClassStmt = $conn->prepare($updateClassQuery);
                        $updateClassStmt->execute([$current_class_id, $student_id]);
                        
                        // ตรวจสอบประวัติการศึกษา
                        $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
                        $yearStmt = $conn->prepare($yearQuery);
                        $yearStmt->execute([$current_class_id]);
                        $academic_year_id = $yearStmt->fetchColumn();
                        
                        if ($academic_year_id) {
                            $recordQuery = "SELECT record_id FROM student_academic_records 
                                          WHERE student_id = ? AND academic_year_id = ?";
                            $recordStmt = $conn->prepare($recordQuery);
                            $recordStmt->execute([$student_id, $academic_year_id]);
                            $record_id = $recordStmt->fetchColumn();
                            
                            if ($record_id) {
                                // อัปเดตประวัติการศึกษา
                                $updateRecordQuery = "UPDATE student_academic_records 
                                                   SET class_id = ?, updated_at = CURRENT_TIMESTAMP
                                                   WHERE record_id = ?";
                                $updateRecordStmt = $conn->prepare($updateRecordQuery);
                                $updateRecordStmt->execute([$current_class_id, $record_id]);
                            } else {
                                // สร้างประวัติการศึกษาใหม่
                                $newRecordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                                VALUES (?, ?, ?)";
                                $newRecordStmt = $conn->prepare($newRecordQuery);
                                $newRecordStmt->execute([$student_id, $academic_year_id, $current_class_id]);
                            }
                        }
                    }
                    
                    $updated++;
                } else if (!$checkResult) {
                    // สร้างข้อมูลนักเรียนใหม่
                    // สร้างชื่อไลน์ชั่วคราวที่ไม่ซ้ำกัน
                    $tempLineId = 'TEMP_' . $student_code . '_' . time() . '_' . substr(md5(rand()), 0, 6);
                    
                    // สร้างข้อมูลในตาราง users
                    $userQuery = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                                VALUES (?, 'student', ?, ?, ?, ?, ?, 1)";
                    $userStmt = $conn->prepare($userQuery);
                    $userStmt->execute([
                        $tempLineId,
                        $title,
                        $firstname,
                        $lastname,
                        $phone,
                        $email
                    ]);
                    
                    $user_id = $conn->lastInsertId();
                    
                    // กำหนดชั้นเรียน
                    $current_class_id = null;
                    
                    // ถ้ามีการระบุชั้นเรียนโดยตรง
                    if ($class_id) {
                        $current_class_id = $class_id;
                    } else if ($level && $group && $department) {
                        // ค้นหาชั้นเรียนตามระดับ กลุ่ม และแผนก
                        $findClassQuery = "SELECT c.class_id FROM classes c 
                                        JOIN departments d ON c.department_id = d.department_id
                                        JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                                        WHERE c.level = ? AND c.group_number = ? AND d.department_name = ? AND ay.is_active = 1";
                        $findClassStmt = $conn->prepare($findClassQuery);
                        $findClassStmt->execute([$level, $group, $department]);
                        $current_class_id = $findClassStmt->fetchColumn();
                    }
                    
                    // สร้างข้อมูลในตาราง students
                    $studentQuery = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                                   VALUES (?, ?, ?, ?, ?)";
                    $studentStmt = $conn->prepare($studentQuery);
                    $studentStmt->execute([
                        $user_id,
                        $student_code,
                        $title,
                        $current_class_id,
                        $status
                    ]);
                    
                    $student_id = $conn->lastInsertId();
                    
                    // สร้างประวัติการศึกษาถ้ามีชั้นเรียน
                    if ($current_class_id) {
                        $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
                        $yearStmt = $conn->prepare($yearQuery);
                        $yearStmt->execute([$current_class_id]);
                        $academic_year_id = $yearStmt->fetchColumn();
                        
                        if ($academic_year_id) {
                            $recordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                         VALUES (?, ?, ?)";
                            $recordStmt = $conn->prepare($recordQuery);
                            $recordStmt->execute([$student_id, $academic_year_id, $current_class_id]);
                        }
                    }
                    
                    $imported++;
                } else {
                    // ข้ามรายการถ้าไม่อัปเดตข้อมูลที่มีอยู่แล้ว
                    $skipped++;
                    $errors[] = "แถวที่ " . ($i + 1) . ": รหัสนักเรียน " . $student_code . " มีอยู่ในระบบแล้ว (ข้ามเนื่องจากไม่ได้เลือกอัปเดตข้อมูลที่มีอยู่)";
                }
            } catch (PDOException $e) {
                $skipped++;
                $errors[] = "แถวที่ " . ($i + 1) . ": " . $e->getMessage();
            }
        }
        
        $conn->commit();
        
        $message = "นำเข้าข้อมูลสำเร็จ: เพิ่มใหม่ $imported รายการ, อัปเดต $updated รายการ, ข้าม $skipped รายการ";
        
        return [
            'success' => true,
            'message' => $message,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log("Error importing students: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()
        ];
    }
}
/**
 * ดึงข้อมูลสถิตินักเรียน
 * 
 * @return array ข้อมูลสถิติ
 */
function getStudentStatistics() {
    try {
        $conn = getDB();
        
        // ดึงจำนวนนักเรียนทั้งหมด
        $totalQuery = "SELECT COUNT(*) as total FROM students WHERE status = 'กำลังศึกษา'";
        $totalStmt = $conn->query($totalQuery);
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // ดึงจำนวนนักเรียนชาย
        $maleQuery = "SELECT COUNT(*) as male 
                     FROM students s 
                     JOIN users u ON s.user_id = u.user_id 
                     WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นาย', 'เด็กชาย')";
        $maleStmt = $conn->query($maleQuery);
        $male = $maleStmt->fetch(PDO::FETCH_ASSOC)['male'];
        
        // ดึงจำนวนนักเรียนหญิง
        $femaleQuery = "SELECT COUNT(*) as female 
                       FROM students s 
                       JOIN users u ON s.user_id = u.user_id 
                       WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นางสาว', 'เด็กหญิง', 'นาง')";
        $femaleStmt = $conn->query($femaleQuery);
        $female = $femaleStmt->fetch(PDO::FETCH_ASSOC)['female'];
        
        // ดึงจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
        $riskQuery = "SELECT COUNT(*) as risk FROM risk_students WHERE risk_level IN ('high', 'critical')";
        $riskStmt = $conn->query($riskQuery);
        $risk = $riskStmt->fetch(PDO::FETCH_ASSOC)['risk'];
        
        return [
            'total' => (int)$total,
            'male' => (int)$male,
            'female' => (int)$female,
            'risk' => (int)$risk
        ];
    } catch (PDOException $e) {
        error_log("Error getting student statistics: " . $e->getMessage());
        
        return [
            'total' => 0,
            'male' => 0,
            'female' => 0,
            'risk' => 0
        ];
    }
}

/**
 * ดึงข้อมูลชั้นเรียนทั้งหมด
 * 
 * @return array ข้อมูลชั้นเรียน
 */
function getAllClasses() {
    try {
        $conn = getDB();
        
        $query = "SELECT c.class_id, c.level, c.group_number, d.department_name, d.department_id
                 FROM classes c
                 JOIN departments d ON c.department_id = d.department_id
                 JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                 WHERE ay.is_active = 1
                 ORDER BY c.level, c.group_number";
        
        $stmt = $conn->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting classes: " . $e->getMessage());
        
        return [];
    }
}

/**
 * ดึงข้อมูลครูที่ปรึกษาทั้งหมด
 * 
 * @return array ข้อมูลครูที่ปรึกษา
 */
function getAllAdvisors() {
    try {
        $conn = getDB();
        
        $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, 
                 d.department_name, d.department_id
                 FROM teachers t
                 LEFT JOIN departments d ON t.department_id = d.department_id
                 ORDER BY t.first_name, t.last_name";
        
        $stmt = $conn->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting advisors: " . $e->getMessage());
        
        return [];
    }
}
?>
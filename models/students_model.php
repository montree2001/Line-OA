<?php
/**
 * students_model.php - ฟังก์ชันจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// ฟังก์ชันดึงข้อมูลนักเรียนทั้งหมด
function getAllStudents($filters = []) {
    $conn = getDB();
    try {
        $sql = "SELECT s.student_id, s.student_code, s.status, 
                u.title, u.first_name, u.last_name, u.line_id, u.profile_picture, 
                c.level, c.group_number, d.department_name,
                sar.total_attendance_days, sar.total_absence_days
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id 
                    AND sar.academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1)
                WHERE 1=1";
        
        $params = [];
        
        // เพิ่มเงื่อนไขการกรอง
        if (!empty($filters['name'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
            $params[] = "%{$filters['name']}%";
            $params[] = "%{$filters['name']}%";
        }
        
        if (!empty($filters['student_code'])) {
            $sql .= " AND s.student_code LIKE ?";
            $params[] = "%{$filters['student_code']}%";
        }
        
        if (!empty($filters['level'])) {
            $sql .= " AND c.level = ?";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['room'])) {
            $sql .= " AND c.group_number = ?";
            $params[] = $filters['room'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] == 'เสี่ยงตกกิจกรรม') {
                $sql .= " AND ((sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 60)";
            } elseif ($filters['status'] == 'ต้องระวัง') {
                $sql .= " AND ((sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 60 AND 75)";
            } elseif ($filters['status'] == 'ปกติ') {
                $sql .= " AND ((sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 75)";
            }
        }
        
        $sql .= " ORDER BY c.level, c.group_number, u.first_name, u.last_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // คำนวณข้อมูลเพิ่มเติมสำหรับแต่ละนักเรียน
        foreach ($students as &$student) {
            // คำนวณอัตราการเข้าแถว
            $total_days = ($student['total_attendance_days'] ?? 0) + ($student['total_absence_days'] ?? 0);
            if ($total_days > 0) {
                $student['attendance_rate'] = ($student['total_attendance_days'] / $total_days) * 100;
            } else {
                $student['attendance_rate'] = 100; // ถ้าไม่มีข้อมูล ให้เป็น 100%
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
            
            // สร้างชื่อชั้นเรียน
            $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
        }
        
        return $students;
    } catch (PDOException $e) {
        error_log("Error getting students: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงข้อมูลนักเรียนตาม ID
function getStudentById($student_id) {
    $conn = getDB();
    try {
        $sql = "SELECT s.*, 
                u.title, u.first_name, u.last_name, u.line_id, u.profile_picture, u.phone_number, u.email,
                c.level, c.group_number, d.department_name,
                ca.teacher_id,
                sar.total_attendance_days, sar.total_absence_days
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
                LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id 
                    AND sar.academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1)
                WHERE s.student_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            // คำนวณอัตราการเข้าแถว
            $total_days = ($student['total_attendance_days'] ?? 0) + ($student['total_absence_days'] ?? 0);
            if ($total_days > 0) {
                $student['attendance_rate'] = ($student['total_attendance_days'] / $total_days) * 100;
            } else {
                $student['attendance_rate'] = 100; // ถ้าไม่มีข้อมูล ให้เป็น 100%
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
            
            // สร้างชื่อชั้นเรียน
            $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
            
            // ดึงข้อมูลครูที่ปรึกษา
            if (!empty($student['teacher_id'])) {
                $teacherSql = "SELECT 
                               t.teacher_id, 
                               CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                               t.position 
                               FROM teachers t 
                               WHERE t.teacher_id = ?";
                $teacherStmt = $conn->prepare($teacherSql);
                $teacherStmt->execute([$student['teacher_id']]);
                $student['advisor'] = $teacherStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // ดึงข้อมูลผู้ปกครอง
            $parentSql = "SELECT 
                         p.parent_id, 
                         u.title, u.first_name, u.last_name, u.phone_number, u.line_id,
                         p.relationship
                         FROM parent_student_relation psr
                         JOIN parents p ON psr.parent_id = p.parent_id
                         JOIN users u ON p.user_id = u.user_id
                         WHERE psr.student_id = ?";
            $parentStmt = $conn->prepare($parentSql);
            $parentStmt->execute([$student_id]);
            $student['parents'] = $parentStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $student;
    } catch (PDOException $e) {
        error_log("Error getting student details: " . $e->getMessage());
        return null;
    }
}

// ฟังก์ชันเพิ่มนักเรียนใหม่
function addStudent($data) {
    $conn = getDB();
    try {
        $conn->beginTransaction();
        
        // 1. เพิ่มข้อมูลในตาราง users ก่อน
        $userSql = "INSERT INTO users (role, title, first_name, last_name, phone_number, email, gdpr_consent)
                   VALUES ('student', ?, ?, ?, ?, ?, 1)";
        $userStmt = $conn->prepare($userSql);
        $userStmt->execute([
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['phone_number'] ?? null,
            $data['email'] ?? null
        ]);
        
        $userId = $conn->lastInsertId();
        
        // 2. เพิ่มข้อมูลในตาราง students
        $studentSql = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                      VALUES (?, ?, ?, ?, 'กำลังศึกษา')";
        $studentStmt = $conn->prepare($studentSql);
        $studentStmt->execute([
            $userId,
            $data['student_id'],
            $data['title'],
            $data['class_id'] ?? null
        ]);
        
        $studentId = $conn->lastInsertId();
        
        // 3. สร้างประวัติการศึกษาถ้ามีชั้นเรียน
        if (!empty($data['class_id'])) {
            // ดึง academic_year_id ปัจจุบัน
            $acadYearSql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
            $acadYearStmt = $conn->prepare($acadYearSql);
            $acadYearStmt->execute();
            $academicYearId = $acadYearStmt->fetchColumn();
            
            if ($academicYearId) {
                $recordSql = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                             VALUES (?, ?, ?)";
                $recordStmt = $conn->prepare($recordSql);
                $recordStmt->execute([
                    $studentId,
                    $academicYearId,
                    $data['class_id']
                ]);
            }
        }
        
        // 4. เพิ่มข้อมูลผู้ปกครองถ้ามี
        if (!empty($data['parent_name'])) {
            // เพิ่มผู้ปกครองในตาราง users
            $parentUserSql = "INSERT INTO users (role, title, first_name, last_name, phone_number, gdpr_consent)
                            VALUES ('parent', 'นาย/นาง/นางสาว', ?, '', ?, 1)";
            $parentUserStmt = $conn->prepare($parentUserSql);
            $parentUserStmt->execute([
                $data['parent_name'],
                $data['parent_phone'] ?? null
            ]);
            
            $parentUserId = $conn->lastInsertId();
            
            // เพิ่มในตาราง parents
            $parentSql = "INSERT INTO parents (user_id, relationship)
                         VALUES (?, ?)";
            $parentStmt = $conn->prepare($parentSql);
            $parentStmt->execute([
                $parentUserId,
                $data['parent_relation'] ?? 'ผู้ปกครอง'
            ]);
            
            $parentId = $conn->lastInsertId();
            
            // เชื่อมความสัมพันธ์กับนักเรียน
            $relationSql = "INSERT INTO parent_student_relation (parent_id, student_id)
                           VALUES (?, ?)";
            $relationStmt = $conn->prepare($relationSql);
            $relationStmt->execute([
                $parentId,
                $studentId
            ]);
        }
        
        $conn->commit();
        return [
            'success' => true,
            'message' => 'เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว',
            'student_id' => $studentId
        ];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error adding student: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันแก้ไขข้อมูลนักเรียน
function updateStudent($data) {
    $conn = getDB();
    try {
        $conn->beginTransaction();
        
        // ดึงข้อมูล user_id ของนักเรียน
        $getUserIdSql = "SELECT user_id FROM students WHERE student_id = ?";
        $getUserIdStmt = $conn->prepare($getUserIdSql);
        $getUserIdStmt->execute([$data['student_id']]);
        $userId = $getUserIdStmt->fetchColumn();
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ];
        }
        
        // 1. อัปเดตข้อมูลในตาราง users
        $userSql = "UPDATE users SET 
                   title = ?, 
                   first_name = ?, 
                   last_name = ?, 
                   phone_number = ?, 
                   email = ?
                   WHERE user_id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->execute([
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['phone_number'] ?? null,
            $data['email'] ?? null,
            $userId
        ]);
        
        // 2. อัปเดตข้อมูลในตาราง students
        $studentSql = "UPDATE students SET 
                      student_code = ?, 
                      title = ?, 
                      current_class_id = ?, 
                      status = ?
                      WHERE student_id = ?";
        $studentStmt = $conn->prepare($studentSql);
        $studentStmt->execute([
            $data['student_code'],
            $data['title'],
            $data['class_id'] ?? null,
            $data['status'],
            $data['student_id']
        ]);
        
        // 3. อัปเดตประวัติการศึกษาถ้ามีชั้นเรียน
        if (!empty($data['class_id'])) {
            // ดึง academic_year_id ปัจจุบัน
            $acadYearSql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
            $acadYearStmt = $conn->prepare($acadYearSql);
            $acadYearStmt->execute();
            $academicYearId = $acadYearStmt->fetchColumn();
            
            if ($academicYearId) {
                // ตรวจสอบว่ามีประวัติการศึกษาสำหรับปีการศึกษานี้หรือไม่
                $checkRecordSql = "SELECT record_id FROM student_academic_records 
                                  WHERE student_id = ? AND academic_year_id = ?";
                $checkRecordStmt = $conn->prepare($checkRecordSql);
                $checkRecordStmt->execute([
                    $data['student_id'],
                    $academicYearId
                ]);
                
                if ($checkRecordStmt->rowCount() > 0) {
                    // อัปเดตประวัติการศึกษา
                    $updateRecordSql = "UPDATE student_academic_records 
                                       SET class_id = ? 
                                       WHERE student_id = ? AND academic_year_id = ?";
                    $updateRecordStmt = $conn->prepare($updateRecordSql);
                    $updateRecordStmt->execute([
                        $data['class_id'],
                        $data['student_id'],
                        $academicYearId
                    ]);
                } else {
                    // สร้างประวัติการศึกษาใหม่
                    $createRecordSql = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                      VALUES (?, ?, ?)";
                    $createRecordStmt = $conn->prepare($createRecordSql);
                    $createRecordStmt->execute([
                        $data['student_id'],
                        $academicYearId,
                        $data['class_id']
                    ]);
                }
            }
        }
        
        $conn->commit();
        return [
            'success' => true,
            'message' => 'แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error updating student: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันลบข้อมูลนักเรียน
function deleteStudent($student_id) {
    $conn = getDB();
    try {
        $conn->beginTransaction();
        
        // ดึงข้อมูล user_id ของนักเรียน
        $getUserIdSql = "SELECT user_id FROM students WHERE student_id = ?";
        $getUserIdStmt = $conn->prepare($getUserIdSql);
        $getUserIdStmt->execute([$student_id]);
        $userId = $getUserIdStmt->fetchColumn();
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ];
        }
        
        // 1. ลบข้อมูลความสัมพันธ์กับผู้ปกครอง
        $deleteRelationSql = "DELETE FROM parent_student_relation WHERE student_id = ?";
        $deleteRelationStmt = $conn->prepare($deleteRelationSql);
        $deleteRelationStmt->execute([$student_id]);
        
        // 2. ลบประวัติการศึกษา
        $deleteRecordSql = "DELETE FROM student_academic_records WHERE student_id = ?";
        $deleteRecordStmt = $conn->prepare($deleteRecordSql);
        $deleteRecordStmt->execute([$student_id]);
        
        // 3. ลบประวัติการเข้าแถว
        $deleteAttendanceSql = "DELETE FROM attendance WHERE student_id = ?";
        $deleteAttendanceStmt = $conn->prepare($deleteAttendanceSql);
        $deleteAttendanceStmt->execute([$student_id]);
        
        // 4. ลบข้อมูลความเสี่ยง
        $deleteRiskSql = "DELETE FROM risk_students WHERE student_id = ?";
        $deleteRiskStmt = $conn->prepare($deleteRiskSql);
        $deleteRiskStmt->execute([$student_id]);
        
        // 5. ลบข้อมูลการเลื่อนชั้น
        $deleteHistorySql = "DELETE FROM class_history WHERE student_id = ?";
        $deleteHistoryStmt = $conn->prepare($deleteHistorySql);
        $deleteHistoryStmt->execute([$student_id]);
        
        // 6. ลบข้อมูลนักเรียน
        $deleteStudentSql = "DELETE FROM students WHERE student_id = ?";
        $deleteStudentStmt = $conn->prepare($deleteStudentSql);
        $deleteStudentStmt->execute([$student_id]);
        
        // 7. ลบข้อมูลผู้ใช้
        $deleteUserSql = "DELETE FROM users WHERE user_id = ?";
        $deleteUserStmt = $conn->prepare($deleteUserSql);
        $deleteUserStmt->execute([$userId]);
        
        $conn->commit();
        return [
            'success' => true,
            'message' => 'ลบข้อมูลนักเรียนเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error deleting student: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันนำเข้าข้อมูลนักเรียนจากไฟล์ Excel
function importStudentsFromExcel($file, $options = []) {
    $conn = getDB();
    try {
        // ตรวจสอบไฟล์
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'
            ];
        }
        
        // ตรวจสอบนามสกุลไฟล์
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array($fileExt, ['xlsx', 'xls'])) {
            return [
                'success' => false,
                'message' => 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) เท่านั้น'
            ];
        }
        
        // ในทางปฏิบัติจริง ต้องใช้ไลบรารี เช่น PhpSpreadsheet หรือ PHPExcel
        require 'vendor/autoload.php';
        
        // อ่านไฟล์ Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // ข้ามแถวแรกถ้าเป็นหัวตาราง
        if ($options['skip_header'] ?? true) {
            array_shift($rows);
        }
        
        $conn->beginTransaction();
        
        $success = 0;
        $failed = 0;
        $updated = 0;
        
        // ดึง academic_year_id ปัจจุบัน
        $acadYearSql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $acadYearStmt = $conn->prepare($acadYearSql);
        $acadYearStmt->execute();
        $academicYearId = $acadYearStmt->fetchColumn();
        
        foreach ($rows as $row) {
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                $failed++;
                continue;
            }
            
            $student_code = $row[0];
            $title = $row[1];
            $firstname = $row[2];
            $lastname = $row[3];
            $level = $row[4] ?? null;
            $room = $row[5] ?? null;
            $advisor = $row[6] ?? null;
            
            // ตรวจสอบว่ามีนักเรียนคนนี้ในระบบหรือไม่
            $checkSql = "SELECT s.student_id, s.user_id 
                        FROM students s 
                        WHERE s.student_code = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$student_code]);
            $existingStudent = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // ถ้ามีชั้นเรียน ให้หา class_id
            $class_id = null;
            if (!empty($level) && !empty($room)) {
                $classSql = "SELECT c.class_id 
                            FROM classes c 
                            JOIN departments d ON c.department_id = d.department_id
                            WHERE c.level = ? AND c.group_number = ? AND c.academic_year_id = ?
                            LIMIT 1";
                $classStmt = $conn->prepare($classSql);
                $classStmt->execute([$level, $room, $academicYearId]);
                $class_id = $classStmt->fetchColumn();
            }
            
            if ($existingStudent) {
                // ถ้าเลือกอัปเดตข้อมูลที่มีอยู่
                if ($options['update_existing'] ?? true) {
                    // อัปเดตข้อมูลผู้ใช้
                    $updateUserSql = "UPDATE users 
                                    SET title = ?, first_name = ?, last_name = ? 
                                    WHERE user_id = ?";
                    $updateUserStmt = $conn->prepare($updateUserSql);
                    $updateUserStmt->execute([
                        $title,
                        $firstname,
                        $lastname,
                        $existingStudent['user_id']
                    ]);
                    
                    // อัปเดตข้อมูลนักเรียน
                    $updateStudentSql = "UPDATE students 
                                        SET title = ?, current_class_id = ? 
                                        WHERE student_id = ?";
                    $updateStudentStmt = $conn->prepare($updateStudentSql);
                    $updateStudentStmt->execute([
                        $title,
                        $class_id,
                        $existingStudent['student_id']
                    ]);
                    
                    // อัปเดตหรือสร้างประวัติการศึกษา
                    if ($class_id && $academicYearId) {
                        $checkRecordSql = "SELECT record_id 
                                          FROM student_academic_records 
                                          WHERE student_id = ? AND academic_year_id = ?";
                        $checkRecordStmt = $conn->prepare($checkRecordSql);
                        $checkRecordStmt->execute([
                            $existingStudent['student_id'],
                            $academicYearId
                        ]);
                        
                        if ($checkRecordStmt->rowCount() > 0) {
                            $updateRecordSql = "UPDATE student_academic_records 
                                              SET class_id = ? 
                                              WHERE student_id = ? AND academic_year_id = ?";
                            $updateRecordStmt = $conn->prepare($updateRecordSql);
                            $updateRecordStmt->execute([
                                $class_id,
                                $existingStudent['student_id'],
                                $academicYearId
                            ]);
                        } else {
                            $insertRecordSql = "INSERT INTO student_academic_records 
                                              (student_id, academic_year_id, class_id) 
                                              VALUES (?, ?, ?)";
                            $insertRecordStmt = $conn->prepare($insertRecordSql);
                            $insertRecordStmt->execute([
                                $existingStudent['student_id'],
                                $academicYearId,
                                $class_id
                            ]);
                        }
                    }
                    
                    $updated++;
                } else {
                    $failed++;
                }
            } else {
                // เพิ่มข้อมูลใหม่
                // 1. เพิ่มข้อมูลในตาราง users ก่อน
                $userSql = "INSERT INTO users (role, title, first_name, last_name, gdpr_consent)
                           VALUES ('student', ?, ?, ?, 1)";
                $userStmt = $conn->prepare($userSql);
                $userStmt->execute([
                    $title,
                    $firstname,
                    $lastname
                ]);
                
                $userId = $conn->lastInsertId();
                
                // 2. เพิ่มข้อมูลในตาราง students
                $studentSql = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                              VALUES (?, ?, ?, ?, 'กำลังศึกษา')";
                $studentStmt = $conn->prepare($studentSql);
                $studentStmt->execute([
                    $userId,
                    $student_code,
                    $title,
                    $class_id
                ]);
                
                $studentId = $conn->lastInsertId();
                
                // 3. สร้างประวัติการศึกษาถ้ามีชั้นเรียน
                if ($class_id && $academicYearId) {
                    $recordSql = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                 VALUES (?, ?, ?)";
                    $recordStmt = $conn->prepare($recordSql);
                    $recordStmt->execute([
                        $studentId,
                        $academicYearId,
                        $class_id
                    ]);
                }
                
                $success++;
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "นำเข้าข้อมูลสำเร็จ: เพิ่มใหม่ $success รายการ, อัปเดต $updated รายการ, ไม่สำเร็จ $failed รายการ"
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

// ฟังก์ชันดึงสถิติจำนวนนักเรียน
function getStudentStatistics() {
    $conn = getDB();
    try {
        // ดึงจำนวนนักเรียนทั้งหมด
        $totalSql = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
        $totalStmt = $conn->prepare($totalSql);
        $totalStmt->execute();
        $total = $totalStmt->fetchColumn();
        
        // ดึงจำนวนนักเรียนชาย
        $maleSql = "SELECT COUNT(*) 
                   FROM students s 
                   JOIN users u ON s.user_id = u.user_id 
                   WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นาย', 'เด็กชาย')";
        $maleStmt = $conn->prepare($maleSql);
        $maleStmt->execute();
        $male = $maleStmt->fetchColumn();
        
        // คำนวณจำนวนนักเรียนหญิง
        $female = $total - $male;
        
        // ดึงจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
        $riskSql = "SELECT COUNT(*) 
                   FROM risk_students
                   WHERE risk_level IN ('high', 'critical')";
        $riskStmt = $conn->prepare($riskSql);
        $riskStmt->execute();
        $risk = $riskStmt->fetchColumn();
        
        return [
            'total' => $total,
            'male' => $male,
            'female' => $female,
            'risk' => $risk
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

// ฟังก์ชันดึงข้อมูลชั้นเรียนทั้งหมด
function getAllClasses() {
    $conn = getDB();
    try {
        $sql = "SELECT c.class_id, c.level, c.group_number, d.department_name 
               FROM classes c 
               JOIN departments d ON c.department_id = d.department_id 
               JOIN academic_years a ON c.academic_year_id = a.academic_year_id 
               WHERE a.is_active = 1 
               ORDER BY c.level, c.group_number";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting classes: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงข้อมูลครูที่ปรึกษาทั้งหมด
function getAllAdvisors() {
    $conn = getDB();
    try {
        $sql = "SELECT t.teacher_id, CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name 
               FROM teachers t 
               ORDER BY t.first_name, t.last_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting advisors: " . $e->getMessage());
        return [];
    }
}
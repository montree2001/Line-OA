<?php
/**
 * api/import_students.php - API สำหรับนำเข้าข้อมูลนักเรียน
 * นำไฟล์นี้ไปไว้ใน admin/api/import_students.php
 */

// ตั้งค่า header
header('Content-Type: application/json; charset=UTF-8');

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบการล็อกอิน (ให้เปิดใช้งานเมื่อพร้อมใช้งานจริง)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'ไม่มีสิทธิ์เข้าถึง API นี้'
//     ]);
//     exit;
// }

// ตรวจสอบว่าเป็นการร้องขอแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'วิธีการร้องขอไม่ถูกต้อง'
    ]);
    exit;
}

// ตรวจสอบ action
if (!isset($_POST['action']) || $_POST['action'] !== 'import') {
    echo json_encode([
        'success' => false,
        'message' => 'พารามิเตอร์ไม่ถูกต้อง'
    ]);
    exit;
}

// ดำเนินการนำเข้าข้อมูล
try {
    // ตรวจสอบวิธีการนำเข้า
    if (isset($_POST['mapped_data'])) {
        // นำเข้าจากข้อมูลที่แม็ปแล้ว (JSON)
        $result = importFromMappedData($_POST);
    } else if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        // นำเข้าจากไฟล์
        $result = importFromFile($_FILES['import_file'], $_POST);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลหรือไฟล์นำเข้า'
        ]);
        exit;
    }
    
    // ส่งผลลัพธ์
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()
    ]);
}

/**
 * นำเข้าข้อมูลจากข้อมูลที่แม็ปแล้ว
 * 
 * @param array $data ข้อมูลจากการ POST
 * @return array ผลลัพธ์การนำเข้า
 */
function importFromMappedData($data) {
    // แปลงข้อมูล JSON
    $mappedData = json_decode($data['mapped_data'], true);
    
    if (!is_array($mappedData) || empty($mappedData)) {
        return [
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้องหรือไม่มีข้อมูลที่จะนำเข้า'
        ];
    }
    
    // ดำเนินการนำเข้าข้อมูล
    return processImport($mappedData, $data);
}

/**
 * นำเข้าข้อมูลจากไฟล์
 * 
 * @param array $file ข้อมูลไฟล์
 * @param array $options ตัวเลือกการนำเข้า
 * @return array ผลลัพธ์การนำเข้า
 */
function importFromFile($file, $options) {
    // ตรวจสอบนามสกุลไฟล์
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        return [
            'success' => false,
            'message' => 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) หรือ CSV เท่านั้น'
        ];
    }
    
    // อ่านข้อมูลจากไฟล์
    try {
        // โหลดไลบรารี PhpSpreadsheet
        require_once '../../vendor/autoload.php';
        
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
        
        // ตั้งค่า reader สำหรับ CSV ถ้าเป็นไฟล์ CSV
        if ($ext === 'csv') {
            $reader->setInputEncoding('UTF-8');
        }
        
        $spreadsheet = $reader->load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // ข้ามแถวแรก (หัวตาราง) ถ้าเลือกตัวเลือก skip_header
        $skipHeader = isset($options['skip_header']) && $options['skip_header'] ? true : false;
        $startRow = $skipHeader && count($rows) > 0 ? 1 : 0;
        
        // แม็ปข้อมูลตามตำแหน่งคอลัมน์มาตรฐาน (ใช้เมื่อไม่ได้แม็ปฟิลด์)
        $mappedData = [];
        
        for ($i = $startRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                continue; // ข้ามแถวที่ข้อมูลไม่ครบ
            }
            
            $mappedData[] = [
                'student_code' => trim($row[0]),
                'title' => trim($row[1]),
                'firstname' => trim($row[2]),
                'lastname' => trim($row[3]),
                'phone' => isset($row[4]) ? trim($row[4]) : '',
                'email' => isset($row[5]) ? trim($row[5]) : '',
                'level' => isset($row[6]) ? trim($row[6]) : '',
                'group' => isset($row[7]) ? trim($row[7]) : '',
                'department' => isset($row[8]) ? trim($row[8]) : '',
                'status' => isset($row[9]) ? trim($row[9]) : 'กำลังศึกษา'
            ];
        }
        
        // ดำเนินการนำเข้าข้อมูล
        return processImport($mappedData, $options);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการอ่านไฟล์: ' . $e->getMessage()
        ];
    }
}

/**
 * ประมวลผลการนำเข้าข้อมูล
 * 
 * @param array $mappedData ข้อมูลที่แม็ปแล้ว
 * @param array $options ตัวเลือกการนำเข้า
 * @return array ผลลัพธ์การนำเข้า
 */
function processImport($mappedData, $options) {
    // สร้างการเชื่อมต่อฐานข้อมูล
    $conn = getDB();
    $conn->beginTransaction();
    
    // ตัวแปรสำหรับนับจำนวนการนำเข้า
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    // กำหนดชั้นเรียนสำหรับนักเรียนที่นำเข้า
    $class_id = isset($options['import_class_id']) && !empty($options['import_class_id']) 
        ? $options['import_class_id'] : null;
    
    // อัพเดตข้อมูลที่มีอยู่หรือไม่
    $updateExisting = isset($options['update_existing']) && $options['update_existing'] ? true : false;
    
    try {
        // วนลูปสำหรับแต่ละรายการ
        foreach ($mappedData as $index => $student) {
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($student['student_code']) || empty($student['title']) || 
                empty($student['firstname']) || empty($student['lastname'])) {
                $skipped++;
                $errors[] = "รายการที่ " . ($index + 1) . ": ข้อมูลไม่ครบถ้วน";
                continue;
            }
            
            // ตรวจสอบค่าที่รับได้
            $allowedTitles = ['นาย', 'นางสาว', 'เด็กชาย', 'เด็กหญิง', 'นาง'];
            if (!in_array($student['title'], $allowedTitles)) {
                $skipped++;
                $errors[] = "รายการที่ " . ($index + 1) . ": คำนำหน้าไม่ถูกต้อง (รองรับเฉพาะ นาย, นางสาว, เด็กชาย, เด็กหญิง, นาง)";
                continue;
            }
            
            $allowedStatus = ['กำลังศึกษา', 'พักการเรียน', 'พ้นสภาพ', 'สำเร็จการศึกษา'];
            if (!empty($student['status']) && !in_array($student['status'], $allowedStatus)) {
                $student['status'] = 'กำลังศึกษา'; // กำหนดค่าเริ่มต้น
            }
            
            // ดำเนินการนำเข้าหรืออัพเดต
            try {
                // ตรวจสอบว่ามีนักเรียนอยู่แล้วหรือไม่
                $stmt = $conn->prepare("SELECT s.student_id, s.user_id FROM students s WHERE s.student_code = ?");
                $stmt->execute([$student['student_code']]);
                $existingStudent = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingStudent && $updateExisting) {
                    // อัพเดตข้อมูลนักเรียนที่มีอยู่แล้ว
                    $result = updateExistingStudent($conn, $existingStudent, $student, $class_id);
                    
                    if ($result) {
                        $updated++;
                    } else {
                        $skipped++;
                        $errors[] = "รายการที่ " . ($index + 1) . ": อัพเดตข้อมูลไม่สำเร็จ";
                    }
                } else if (!$existingStudent) {
                    // เพิ่มนักเรียนใหม่
                    $result = addNewStudent($conn, $student, $class_id);
                    
                    if ($result) {
                        $imported++;
                    } else {
                        $skipped++;
                        $errors[] = "รายการที่ " . ($index + 1) . ": เพิ่มข้อมูลไม่สำเร็จ";
                    }
                } else {
                    // ข้ามรายการที่มีอยู่แล้วและไม่ต้องการอัพเดต
                    $skipped++;
                    $errors[] = "รายการที่ " . ($index + 1) . ": รหัสนักเรียน " . $student['student_code'] . " มีอยู่ในระบบแล้ว (ข้ามเนื่องจากไม่ได้เลือกอัพเดตข้อมูลที่มีอยู่)";
                }
            } catch (Exception $e) {
                $skipped++;
                $errors[] = "รายการที่ " . ($index + 1) . ": " . $e->getMessage();
                
                // บันทึกข้อผิดพลาดเพื่อการแก้ไขปัญหา
                error_log("Error importing student {$student['student_code']}: " . $e->getMessage());
            }
        }
        
        // ยืนยันการทำรายการ
        $conn->commit();
        
        // สร้างข้อความสรุป
        $message = "นำเข้าข้อมูลสำเร็จ: เพิ่มใหม่ {$imported} รายการ, อัพเดต {$updated} รายการ, ข้าม {$skipped} รายการ";
        
        return [
            'success' => true,
            'message' => $message,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        // ยกเลิกการทำรายการ
        $conn->rollBack();
        
        // บันทึกข้อผิดพลาด
        error_log("Error in processImport: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()
        ];
    }
}

/**
 * เพิ่มนักเรียนใหม่
 * 
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 * @param array $student ข้อมูลนักเรียน
 * @param int|null $class_id รหัสชั้นเรียน (ถ้ามี)
 * @return bool สถานะการดำเนินการ
 */
function addNewStudent($conn, $student, $class_id = null) {
    // สร้าง line_id ชั่วคราวที่ไม่ซ้ำกัน
    $tempLineId = 'TEMP_' . $student['student_code'] . '_' . time() . '_' . bin2hex(random_bytes(4));
    
    // กำหนดค่าเริ่มต้น
    $student['status'] = $student['status'] ?? 'กำลังศึกษา';
    
    // 1. เพิ่มข้อมูลในตาราง users
    $userQuery = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                VALUES (?, 'student', ?, ?, ?, ?, ?, 1)";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([
        $tempLineId,
        $student['title'],
        $student['firstname'],
        $student['lastname'],
        $student['phone'] ?? '',
        $student['email'] ?? ''
    ]);
    
    $user_id = $conn->lastInsertId();
    
    // กำหนดชั้นเรียน
    $current_class_id = null;
    
    // ใช้ชั้นเรียนที่ระบุโดยตรง
    if ($class_id) {
        $current_class_id = $class_id;
    } else if (!empty($student['level']) && !empty($student['group']) && !empty($student['department'])) {
        // ค้นหาชั้นเรียนตามข้อมูลที่ระบุ
        $findClassQuery = "SELECT c.class_id FROM classes c 
                         JOIN departments d ON c.department_id = d.department_id
                         JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                         WHERE c.level = ? AND c.group_number = ? AND d.department_name = ? AND ay.is_active = 1";
        $findClassStmt = $conn->prepare($findClassQuery);
        $findClassStmt->execute([$student['level'], $student['group'], $student['department']]);
        $current_class_id = $findClassStmt->fetchColumn();
    }
    
    // 2. เพิ่มข้อมูลในตาราง students
    $studentQuery = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                   VALUES (?, ?, ?, ?, ?)";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->execute([
        $user_id,
        $student['student_code'],
        $student['title'],
        $current_class_id,
        $student['status']
    ]);
    
    $student_id = $conn->lastInsertId();
    
    // 3. เพิ่มประวัติการศึกษาถ้ามีชั้นเรียน
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
    
    return true;
}

/**
 * อัพเดตข้อมูลนักเรียนที่มีอยู่แล้ว
 * 
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 * @param array $existingStudent ข้อมูลนักเรียนที่มีอยู่
 * @param array $student ข้อมูลนักเรียนใหม่
 * @param int|null $class_id รหัสชั้นเรียน (ถ้ามี)
 * @return bool สถานะการดำเนินการ
 */
function updateExistingStudent($conn, $existingStudent, $student, $class_id = null) {
    $student_id = $existingStudent['student_id'];
    $user_id = $existingStudent['user_id'];
    
    // กำหนดค่าเริ่มต้น
    $student['status'] = $student['status'] ?? 'กำลังศึกษา';
    
    // 1. อัพเดตข้อมูลในตาราง users
    $userQuery = "UPDATE users SET 
                title = ?, 
                first_name = ?, 
                last_name = ?, 
                phone_number = ?, 
                email = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([
        $student['title'],
        $student['firstname'],
        $student['lastname'],
        $student['phone'] ?? '',
        $student['email'] ?? '',
        $user_id
    ]);
    
    // กำหนดชั้นเรียน
    $current_class_id = null;
    
    // ใช้ชั้นเรียนที่ระบุโดยตรง
    if ($class_id) {
        $current_class_id = $class_id;
    } else if (!empty($student['level']) && !empty($student['group']) && !empty($student['department'])) {
        // ค้นหาชั้นเรียนตามข้อมูลที่ระบุ
        $findClassQuery = "SELECT c.class_id FROM classes c 
                         JOIN departments d ON c.department_id = d.department_id
                         JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                         WHERE c.level = ? AND c.group_number = ? AND d.department_name = ? AND ay.is_active = 1";
        $findClassStmt = $conn->prepare($findClassQuery);
        $findClassStmt->execute([$student['level'], $student['group'], $student['department']]);
        $current_class_id = $findClassStmt->fetchColumn();
    }
    
    // 2. อัพเดตข้อมูลในตาราง students
    $studentQuery = "UPDATE students SET 
                   title = ?, 
                   status = ?,
                   updated_at = CURRENT_TIMESTAMP
                   WHERE student_id = ?";
    $studentParams = [
        $student['title'],
        $student['status'],
        $student_id
    ];
    
    // เพิ่มการอัพเดตชั้นเรียนถ้ามี
    if ($current_class_id) {
        $studentQuery = "UPDATE students SET 
                       title = ?, 
                       status = ?,
                       current_class_id = ?,
                       updated_at = CURRENT_TIMESTAMP
                       WHERE student_id = ?";
        $studentParams = [
            $student['title'],
            $student['status'],
            $current_class_id,
            $student_id
        ];
    }
    
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->execute($studentParams);
    
    // 3. เพิ่มหรืออัพเดตประวัติการศึกษาถ้ามีชั้นเรียน
    if ($current_class_id) {
        $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
        $yearStmt = $conn->prepare($yearQuery);
        $yearStmt->execute([$current_class_id]);
        $academic_year_id = $yearStmt->fetchColumn();
        
        if ($academic_year_id) {
            // ตรวจสอบว่ามีประวัติการศึกษาในปีการศึกษานี้หรือไม่
            $recordQuery = "SELECT record_id FROM student_academic_records 
                          WHERE student_id = ? AND academic_year_id = ?";
            $recordStmt = $conn->prepare($recordQuery);
            $recordStmt->execute([$student_id, $academic_year_id]);
            $record_id = $recordStmt->fetchColumn();
            
            if ($record_id) {
                // อัพเดตประวัติการศึกษาที่มีอยู่แล้ว
                $updateRecordQuery = "UPDATE student_academic_records 
                                    SET class_id = ?, 
                                    updated_at = CURRENT_TIMESTAMP
                                    WHERE record_id = ?";
                $updateRecordStmt = $conn->prepare($updateRecordQuery);
                $updateRecordStmt->execute([$current_class_id, $record_id]);
            } else {
                // สร้างประวัติการศึกษาใหม่
                $newRecordQuery = "INSERT INTO student_academic_records 
                                 (student_id, academic_year_id, class_id)
                                 VALUES (?, ?, ?)";
                $newRecordStmt = $conn->prepare($newRecordQuery);
                $newRecordStmt->execute([$student_id, $academic_year_id, $current_class_id]);
            }
        }
    }
    
    return true;
}
?>
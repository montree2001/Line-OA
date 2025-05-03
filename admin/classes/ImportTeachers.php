<?php
/**
 * ImportTeachers.php - คลาสสำหรับนำเข้าข้อมูลครูจากไฟล์ Excel
 * ระบบ STUDENT-Prasat
 */

class ImportTeachers {
    private $db;
    
    /**
     * คอนสตรัคเตอร์
     */
    public function __construct() {
        // เชื่อมต่อกับฐานข้อมูล
        $this->db = getDB();
    }
    
    /**
     * นำเข้าข้อมูลจากไฟล์ Excel หรือ CSV
     * 
     * @param array $file ข้อมูลไฟล์ที่อัปโหลด
     * @param bool $overwrite อัปเดตข้อมูลที่มีอยู่แล้วหรือไม่
     * @param array $options ตัวเลือกเพิ่มเติม
     * @return array ผลลัพธ์การนำเข้า
     */
    public function import($file, $overwrite = false, $options = []) {
        // ผลลัพธ์เริ่มต้น
        $result = [
            'success' => false,
            'new' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        try {
            // ตรวจสอบไฟล์
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("ไม่พบไฟล์หรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์");
            }
            
            // ตรวจสอบนามสกุลไฟล์
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
                throw new Exception("รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) หรือ CSV เท่านั้น");
            }
            
            // โหลดไลบรารี PhpSpreadsheet
            require_once 'vendor/autoload.php';
            
            // อ่านไฟล์
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            
            // ตั้งค่าสำหรับ CSV (ถ้าเป็น CSV)
            if ($ext === 'csv') {
                $reader->setInputEncoding('UTF-8');
            }
            
            // โหลดเอกสาร
            $spreadsheet = $reader->load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // แปลงข้อมูลเป็นอาร์เรย์
            $data = $worksheet->toArray();
            
            // ตรวจสอบว่ามีข้อมูลหรือไม่
            if (empty($data)) {
                throw new Exception("ไม่พบข้อมูลในไฟล์ที่อัปโหลด");
            }
            
            // ตัวแปรการแม็ปคอลัมน์จาก POST
            $columnMapping = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'map_') === 0 && $value != -1) {
                    $field = str_replace('map_', '', $key);
                    $columnMapping[$field] = (int)$value;
                }
            }
            
            // ตรวจสอบฟิลด์ที่จำเป็น
            $requiredFields = ['national_id', 'title', 'firstname', 'lastname'];
            foreach ($requiredFields as $field) {
                if (!isset($columnMapping[$field])) {
                    throw new Exception("ไม่พบการแม็ปคอลัมน์สำหรับฟิลด์ '$field'");
                }
            }
            
            // ตัวเลือกการนำเข้า
            $skipHeader = isset($_POST['skip_header']) && $_POST['skip_header'] === 'on';
            $updateExisting = isset($_POST['update_existing']) && $_POST['update_existing'] === 'on';
            $departmentId = !empty($_POST['import_department_id']) ? $this->getDepartmentIdByName($_POST['import_department_id']) : null;
            
            // เริ่มการนำเข้าข้อมูล
            $startRow = $skipHeader ? 1 : 0;
            
            $this->db->beginTransaction();
            
            for ($i = $startRow; $i < count($data); $i++) {
                $row = $data[$i];
                
                // ดึงข้อมูลจากการแม็ปคอลัมน์
                $nationalId = trim($row[$columnMapping['national_id']] ?? '');
                $title = trim($row[$columnMapping['title']] ?? '');
                $firstName = trim($row[$columnMapping['firstname']] ?? '');
                $lastName = trim($row[$columnMapping['lastname']] ?? '');
                
                // ฟิลด์เพิ่มเติม (อาจไม่มีการแม็ป)
                $department = isset($columnMapping['department']) ? trim($row[$columnMapping['department']] ?? '') : '';
                $position = isset($columnMapping['position']) ? trim($row[$columnMapping['position']] ?? '') : '';
                $phone = isset($columnMapping['phone']) ? trim($row[$columnMapping['phone']] ?? '') : '';
                $email = isset($columnMapping['email']) ? trim($row[$columnMapping['email']] ?? '') : '';
                
                // ตรวจสอบข้อมูลที่จำเป็น
                if (empty($nationalId) || empty($title) || empty($firstName) || empty($lastName)) {
                    $result['skipped']++;
                    $result['errors'][] = "แถวที่ " . ($i + 1) . ": ข้อมูลไม่ครบถ้วน";
                    continue;
                }
                
                // ตรวจสอบเลขบัตรประชาชน
                if (!$this->validateNationalId($nationalId)) {
                    $result['skipped']++;
                    $result['errors'][] = "แถวที่ " . ($i + 1) . ": รูปแบบเลขบัตรประชาชนไม่ถูกต้อง";
                    continue;
                }
                
                // ตรวจสอบคำนำหน้า
                $validTitles = ['นาย', 'นาง', 'นางสาว', 'ดร.', 'ผศ.', 'รศ.', 'ศ.', 'อื่นๆ'];
                if (!in_array($title, $validTitles)) {
                    // ถ้าไม่ตรงกับค่าที่กำหนด ใช้ค่าเริ่มต้น
                    $title = 'อาจารย์';
                }
                
                try {
                    // ตรวจสอบว่ามีครูที่มีเลขบัตรประชาชนนี้อยู่แล้วหรือไม่
                    $stmt = $this->db->prepare("SELECT t.teacher_id, t.user_id FROM teachers t WHERE t.national_id = ?");
                    $stmt->execute([$nationalId]);
                    $existingTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingTeacher) {
                        // ถ้ามีข้อมูลอยู่แล้วและอนุญาตให้อัปเดต
                        if ($updateExisting) {
                            // หา department_id
                            $deptId = $departmentId;
                            if (!$deptId && !empty($department)) {
                                $deptId = $this->getDepartmentIdByName($department);
                            }
                            
                            // อัปเดตข้อมูลในตาราง users
                            $this->db->prepare("
                                UPDATE users 
                                SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = NOW() 
                                WHERE user_id = ?
                            ")->execute([
                                $title,
                                $firstName,
                                $lastName,
                                $phone,
                                $email,
                                $existingTeacher['user_id']
                            ]);
                            
                            // อัปเดตข้อมูลในตาราง teachers
                            $this->db->prepare("
                                UPDATE teachers 
                                SET title = ?, department_id = ?, position = ?, first_name = ?, last_name = ?, updated_at = NOW() 
                                WHERE teacher_id = ?
                            ")->execute([
                                $title,
                                $deptId,
                                $position,
                                $firstName,
                                $lastName,
                                $existingTeacher['teacher_id']
                            ]);
                            
                            $result['updated']++;
                        } else {
                            $result['skipped']++;
                            $result['errors'][] = "แถวที่ " . ($i + 1) . ": เลขบัตรประชาชน $nationalId มีอยู่ในระบบแล้ว (ข้ามเนื่องจากไม่ได้เลือกอัปเดตข้อมูลที่มีอยู่)";
                        }
                    } else {
                        // เพิ่มข้อมูลใหม่
                        // หา department_id
                        $deptId = $departmentId;
                        if (!$deptId && !empty($department)) {
                            $deptId = $this->getDepartmentIdByName($department);
                        }
                        
                        // สร้าง line_id ชั่วคราว
                        $tempLineId = 'TEMP_' . $nationalId . '_' . time() . '_' . substr(md5(rand()), 0, 6);
                        
                        // เพิ่มข้อมูลในตาราง users
                        $userStmt = $this->db->prepare("
                            INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                            VALUES (?, 'teacher', ?, ?, ?, ?, ?, 1)
                        ");
                        $userStmt->execute([
                            $tempLineId,
                            $title,
                            $firstName,
                            $lastName,
                            $phone,
                            $email
                        ]);
                        
                        $userId = $this->db->lastInsertId();
                        
                        // เพิ่มข้อมูลในตาราง teachers
                        $teacherStmt = $this->db->prepare("
                            INSERT INTO teachers (user_id, title, national_id, department_id, position, first_name, last_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $teacherStmt->execute([
                            $userId,
                            $title,
                            $nationalId,
                            $deptId,
                            $position,
                            $firstName,
                            $lastName
                        ]);
                        
                        $result['new']++;
                    }
                } catch (PDOException $e) {
                    $result['skipped']++;
                    $result['errors'][] = "แถวที่ " . ($i + 1) . ": " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            $result['success'] = true;
            $result['message'] = "นำเข้าข้อมูลสำเร็จ: เพิ่มใหม่ {$result['new']} รายการ, อัปเดต {$result['updated']} รายการ, ข้าม {$result['skipped']} รายการ";
            
            return $result;
            
        } catch (Exception $e) {
            if ($this->db && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $result['errors'][] = $e->getMessage();
            
            return $result;
        }
    }
    
    /**
     * ตรวจสอบความถูกต้องของเลขบัตรประชาชน
     * 
     * @param string $nationalId เลขบัตรประชาชน
     * @return bool ผลการตรวจสอบ
     */
    private function validateNationalId($nationalId) {
        // ต้องเป็นตัวเลข 13 หลัก
        if (!preg_match('/^\d{13}$/', $nationalId)) {
            return false;
        }
        
        // ตรวจสอบความถูกต้องตามอัลกอริทึมของเลขบัตรประชาชนไทย
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$nationalId[$i] * (13 - $i);
        }
        
        $checkDigit = (11 - ($sum % 11)) % 10;
        
        return $checkDigit === (int)$nationalId[12];
    }
    
    /**
     * ค้นหา department_id จากชื่อแผนก
     * 
     * @param string $departmentName ชื่อแผนก
     * @return int|null รหัสแผนก หรือ null ถ้าไม่พบ
     */
    private function getDepartmentIdByName($departmentName) {
        try {
            // ค้นหาแบบตรงตัว
            $stmt = $this->db->prepare("
                SELECT department_id 
                FROM departments 
                WHERE department_name = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([trim($departmentName)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['department_id'];
            }
            
            // ถ้าไม่เจอแบบตรงตัว ให้ค้นหาแบบคล้ายคลึง
            $stmt = $this->db->prepare("
                SELECT department_id 
                FROM departments 
                WHERE department_name LIKE ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute(['%' . trim($departmentName) . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['department_id'];
            }
            
            // ถ้ายังไม่เจอ ให้ค้นหาจากรหัสแผนก
            $stmt = $this->db->prepare("
                SELECT department_id 
                FROM departments 
                WHERE department_code = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([trim($departmentName)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['department_id'];
            }
            
            // ถ้าไม่เจอเลย ให้ใช้แผนก IT เป็นค่าเริ่มต้น
            $stmt = $this->db->prepare("
                SELECT department_id 
                FROM departments 
                WHERE department_code = 'IT' AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['department_id'];
            }
            
            // ถ้าไม่มีแผนก IT ให้ใช้แผนกแรกที่เจอ
            $stmt = $this->db->query("
                SELECT department_id 
                FROM departments 
                WHERE is_active = 1 
                LIMIT 1
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['department_id'] : null;
            
        } catch (PDOException $e) {
            error_log("Error in getDepartmentIdByName: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * api/import_teachers.php - API สำหรับนำเข้าข้อมูลครูจากไฟล์ Excel
 */

// ตรวจสอบการส่งคำขอแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// โหลดไฟล์ที่จำเป็น
require_once '../db_connect.php';
require_once '../classes/ImportTeachers.php';

// ดำเนินการนำเข้าข้อมูล
try {
    $importer = new ImportTeachers();
    
    // ตรวจสอบไฟล์
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("ไม่พบไฟล์หรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์");
    }
    
    // ตัวเลือกการนำเข้า
    $overwrite = isset($_POST['update_existing']) && $_POST['update_existing'] === 'on';
    
    // นำเข้าข้อมูล
    $result = $importer->import($_FILES['import_file'], $overwrite);
    
    if ($result['success']) {
        // ตั้งค่าข้อความสำเร็จและ redirect กลับไปยังหน้าครูที่ปรึกษา
        $_SESSION['success_message'] = $result['message'];
    } else {
        // ตั้งค่าข้อความข้อผิดพลาดและ redirect กลับไปยังหน้าครูที่ปรึกษา
        $_SESSION['error_message'] = $result['message'];
        if (!empty($result['errors'])) {
            $_SESSION['import_errors'] = $result['errors'];
        }
    }
    
    // Redirect กลับไปยังหน้าครูที่ปรึกษา
    header('Location: ../teachers.php');
    exit;
    
} catch (Exception $e) {
    // ตั้งค่าข้อความข้อผิดพลาดและ redirect กลับไปยังหน้าครูที่ปรึกษา
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการนำเข้าข้อมูล: " . $e->getMessage();
    
    // Redirect กลับไปยังหน้าครูที่ปรึกษา
    header('Location: ../teachers.php');
    exit;
}
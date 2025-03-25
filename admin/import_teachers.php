<?php
/**
 * import_teachers.php - ไฟล์สำหรับการนำเข้าข้อมูลครูจากไฟล์ Excel/CSV
 */

// เริ่มต้น Session สำหรับการจัดการข้อความแจ้งเตือน
session_start();

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ตรวจสอบว่ามีการส่งไฟล์มาหรือไม่
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = "กรุณาเลือกไฟล์ที่ต้องการนำเข้า";
    header("Location: teachers.php");
    exit();
}

// ตรวจสอบประเภทไฟล์
$file_tmp = $_FILES['import_file']['tmp_name'];
$file_name = $_FILES['import_file']['name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// ประเภทไฟล์ที่อนุญาต
$allowed_extensions = ['xlsx', 'xls', 'csv'];

if (!in_array($file_ext, $allowed_extensions)) {
    $_SESSION['error_message'] = "ประเภทไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์ Excel (.xlsx, .xls) หรือ CSV (.csv)";
    header("Location: teachers.php");
    exit();
}

// กำหนดว่าต้องการอัปเดตข้อมูลที่มีอยู่แล้วหรือไม่
$overwrite_existing = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] == 'on';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ตรวจสอบประเภทไฟล์และประมวลผล
    if ($file_ext == 'csv') {
        processCSV($file_tmp, $conn, $overwrite_existing);
    } else {
        processExcel($file_tmp, $conn, $overwrite_existing);
    }
    
    $_SESSION['success_message'] = "นำเข้าข้อมูลครูที่ปรึกษาสำเร็จ";
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการนำเข้าข้อมูล: " . $e->getMessage();
}

header("Location: teachers.php");
exit();

/**
 * ประมวลผลไฟล์ CSV
 * 
 * @param string $file_path ตำแหน่งไฟล์ชั่วคราว
 * @param PDO $conn ออบเจ็กต์เชื่อมต่อฐานข้อมูล
 * @param bool $overwrite_existing อัปเดตข้อมูลที่มีอยู่แล้วหรือไม่
 */
function processCSV($file_path, $conn, $overwrite_existing) {
    // เปิดไฟล์ CSV
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        throw new Exception("ไม่สามารถเปิดไฟล์ CSV ได้");
    }
    
    // อ่านส่วนหัวคอลัมน์
    $header = fgetcsv($handle, 0, ',');
    
    // แปลงชื่อคอลัมน์เป็นตัวพิมพ์เล็กทั้งหมด
    $header = array_map('strtolower', $header);
    
    // ตรวจสอบคอลัมน์ที่จำเป็น
    $required_columns = ['รหัสประจำตัวประชาชน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล'];
    foreach ($required_columns as $col) {
        if (!in_array(strtolower($col), $header)) {
            throw new Exception("ไม่พบคอลัมน์ที่จำเป็น: $col");
        }
    }
    
    // หาตำแหน่งของคอลัมน์
    $column_indexes = [];
    foreach ($header as $i => $col_name) {
        $column_indexes[$col_name] = $i;
    }
    
    // อ่านข้อมูลแต่ละแถว
    $row_count = 0;
    $success_count = 0;
    
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $row_count++;
        
        try {
            // ดึงข้อมูลจากแถว
            $national_id = $row[$column_indexes['รหัสประจำตัวประชาชน']] ?? '';
            $title = $row[$column_indexes['คำนำหน้า']] ?? '';
            $first_name = $row[$column_indexes['ชื่อ']] ?? '';
            $last_name = $row[$column_indexes['นามสกุล']] ?? '';
            $department = $row[$column_indexes['กลุ่มสาระ'] ?? 'แผนก'] ?? '';
            $position = $row[$column_indexes['ตำแหน่ง']] ?? '';
            $phone = $row[$column_indexes['เบอร์โทรศัพท์'] ?? 'โทรศัพท์'] ?? '';
            $email = $row[$column_indexes['อีเมล'] ?? 'email'] ?? '';
            
            // ตรวจสอบข้อมูลครูที่ปรึกษาที่มีอยู่แล้ว
            $stmt = $conn->prepare("SELECT t.teacher_id, t.user_id FROM teachers t WHERE t.national_id = ?");
            $stmt->execute([$national_id]);
            $existing_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_teacher && $overwrite_existing) {
                // อัปเดตข้อมูลที่มีอยู่แล้ว
                updateTeacher($conn, $existing_teacher, $title, $first_name, $last_name, $department, $position, $phone, $email);
            } elseif (!$existing_teacher) {
                // เพิ่มข้อมูลใหม่
                addTeacher($conn, $national_id, $title, $first_name, $last_name, $department, $position, $phone, $email);
            }
            
            $success_count++;
        } catch (Exception $e) {
            // บันทึกข้อผิดพลาดและดำเนินการต่อ
            error_log("Error importing row $row_count: " . $e->getMessage());
            continue;
        }
    }
    
    fclose($handle);
    
    if ($success_count == 0) {
        throw new Exception("ไม่มีข้อมูลที่นำเข้าสำเร็จ");
    }
}

/**
 * ประมวลผลไฟล์ Excel
 * 
 * @param string $file_path ตำแหน่งไฟล์ชั่วคราว
 * @param PDO $conn ออบเจ็กต์เชื่อมต่อฐานข้อมูล
 * @param bool $overwrite_existing อัปเดตข้อมูลที่มีอยู่แล้วหรือไม่
 */
function processExcel($file_path, $conn, $overwrite_existing) {
    // คุณต้องติดตั้งไลบรารี เช่น PhpSpreadsheet ก่อน
    // ในตัวอย่างนี้ ใช้ไลบรารี phpoffice/phpspreadsheet
    
    require 'vendor/autoload.php';
    
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // ดึงข้อมูลเป็นอาร์เรย์
    $data = $worksheet->toArray();
    
    // ตรวจสอบว่ามีข้อมูลหรือไม่
    if (count($data) <= 1) { // มีเฉพาะส่วนหัวหรือไม่มีข้อมูล
        throw new Exception("ไม่พบข้อมูลในไฟล์ Excel");
    }
    
    // ส่วนหัวคอลัมน์
    $header = array_map('strtolower', $data[0]);
    
    // ตรวจสอบคอลัมน์ที่จำเป็น
    $required_columns = ['รหัสประจำตัวประชาชน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล'];
    foreach ($required_columns as $col) {
        if (!in_array(strtolower($col), $header)) {
            throw new Exception("ไม่พบคอลัมน์ที่จำเป็น: $col");
        }
    }
    
    // หาตำแหน่งของคอลัมน์
    $column_indexes = [];
    foreach ($header as $i => $col_name) {
        $column_indexes[$col_name] = $i;
    }
    
    // อ่านข้อมูลแต่ละแถว
    $row_count = 0;
    $success_count = 0;
    
    for ($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        $row_count++;
        
        try {
            // ดึงข้อมูลจากแถว
            $national_id = $row[$column_indexes['รหัสประจำตัวประชาชน']] ?? '';
            $title = $row[$column_indexes['คำนำหน้า']] ?? '';
            $first_name = $row[$column_indexes['ชื่อ']] ?? '';
            $last_name = $row[$column_indexes['นามสกุล']] ?? '';
            $department = $row[$column_indexes['กลุ่มสาระ'] ?? 'แผนก'] ?? '';
            $position = $row[$column_indexes['ตำแหน่ง']] ?? '';
            $phone = $row[$column_indexes['เบอร์โทรศัพท์'] ?? 'โทรศัพท์'] ?? '';
            $email = $row[$column_indexes['อีเมล'] ?? 'email'] ?? '';
            
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($national_id) || empty($first_name) || empty($last_name)) {
                continue; // ข้ามแถวที่ไม่มีข้อมูลสำคัญ
            }
            
            // ตรวจสอบข้อมูลครูที่ปรึกษาที่มีอยู่แล้ว
            $stmt = $conn->prepare("SELECT t.teacher_id, t.user_id FROM teachers t WHERE t.national_id = ?");
            $stmt->execute([$national_id]);
            $existing_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_teacher && $overwrite_existing) {
                // อัปเดตข้อมูลที่มีอยู่แล้ว
                updateTeacher($conn, $existing_teacher, $title, $first_name, $last_name, $department, $position, $phone, $email);
            } elseif (!$existing_teacher) {
                // เพิ่มข้อมูลใหม่
                addTeacher($conn, $national_id, $title, $first_name, $last_name, $department, $position, $phone, $email);
            }
            
            $success_count++;
        } catch (Exception $e) {
            // บันทึกข้อผิดพลาดและดำเนินการต่อ
            error_log("Error importing row $row_count: " . $e->getMessage());
            continue;
        }
    }
    
    if ($success_count == 0) {
        throw new Exception("ไม่มีข้อมูลที่นำเข้าสำเร็จ");
    }
}

/**
 * เพิ่มครูที่ปรึกษาใหม่
 * 
 * @param PDO $conn ออบเจ็กต์เชื่อมต่อฐานข้อมูล
 * @param string $national_id เลขประจำตัวประชาชน
 * @param string $title คำนำหน้า
 * @param string $first_name ชื่อ
 * @param string $last_name นามสกุล
 * @param string $department กลุ่มสาระ/แผนก
 * @param string $position ตำแหน่ง
 * @param string $phone เบอร์โทรศัพท์
 * @param string $email อีเมล
 * @return int teacher_id ของครูที่เพิ่ม
 */
function addTeacher($conn, $national_id, $title, $first_name, $last_name, $department, $position, $phone, $email) {
    // สร้างหมายเลข Line ID สมมติ (ในการใช้งานจริงควรมีกระบวนการลงทะเบียนผ่าน Line)
    $line_id = 'T'.time().rand(1000, 9999);
    
    $conn->beginTransaction();
    
    try {
        // เพิ่มข้อมูลผู้ใช้ก่อน
        $stmt = $conn->prepare("INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email) 
                               VALUES (?, 'teacher', ?, ?, ?, ?, ?)");
        $stmt->execute([$line_id, $title, $first_name, $last_name, $phone, $email]);
        
        // ดึง user_id ที่เพิ่งสร้าง
        $user_id = $conn->lastInsertId();
        
        // เพิ่มข้อมูลครู
        $stmt = $conn->prepare("INSERT INTO teachers (user_id, title, national_id, department, position, first_name, last_name) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $national_id, $department, $position, $first_name, $last_name]);
        
        // ดึง teacher_id ที่เพิ่งสร้าง
        $teacher_id = $conn->lastInsertId();
        
        $conn->commit();
        return $teacher_id;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * อัปเดตข้อมูลครูที่ปรึกษา
 * 
 * @param PDO $conn ออบเจ็กต์เชื่อมต่อฐานข้อมูล
 * @param array $teacher ข้อมูลครูที่มีอยู่แล้ว (teacher_id, user_id)
 * @param string $title คำนำหน้า
 * @param string $first_name ชื่อ
 * @param string $last_name นามสกุล
 * @param string $department กลุ่มสาระ/แผนก
 * @param string $position ตำแหน่ง
 * @param string $phone เบอร์โทรศัพท์
 * @param string $email อีเมล
 */
function updateTeacher($conn, $teacher, $title, $first_name, $last_name, $department, $position, $phone, $email) {
    $conn->beginTransaction();
    
    try {
        // อัปเดตข้อมูลผู้ใช้
        $stmt = $conn->prepare("UPDATE users SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$title, $first_name, $last_name, $phone, $email, $teacher['user_id']]);
        
        // อัปเดตข้อมูลครู
        $stmt = $conn->prepare("UPDATE teachers SET title = ?, department = ?, position = ?, first_name = ?, last_name = ? WHERE teacher_id = ?");
        $stmt->execute([$title, $department, $position, $first_name, $last_name, $teacher['teacher_id']]);
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}
<?php
/**
 * ImportTeachers.php - คลาสสำหรับนำเข้าข้อมูลครูจากไฟล์ Excel
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportTeachers {
    private $db;
    private $teacher;
    
    /**
     * สร้างอินสแตนซ์สำหรับนำเข้าข้อมูล
     */
    public function __construct() {
        $this->db = getDB();
        require_once 'models/Teacher.php';
        $this->teacher = new Teacher();
    }
    
    /**
     * นำเข้าข้อมูลครูจากไฟล์ Excel
     * 
     * @param string $file ไฟล์ที่อัปโหลด ($_FILES['file'])
     * @param bool $overwrite อัปเดตข้อมูลเดิมหรือไม่
     * @return array ผลการนำเข้า
     */
    public function import($file, $overwrite = false) {
        $result = [
            'success' => false,
            'total' => 0,
            'updated' => 0,
            'new' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // ตรวจสอบประเภทไฟล์
        $allowed_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
        if (!in_array($file['type'], $allowed_types)) {
            $result['errors'][] = "ประเภทไฟล์ไม่ถูกต้อง รองรับเฉพาะไฟล์ Excel และ CSV";
            return $result;
        }
        
        // อัปโหลดไฟล์
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $temp_file = $file['tmp_name'];
        $target_file = $upload_dir . basename($file['name']);
        
        if (move_uploaded_file($temp_file, $target_file)) {
            try {
                $this->db->beginTransaction();
                
                $spreadsheet = IOFactory::load($target_file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // ตัดหัวตารางออก
                array_shift($rows);
                
                $result['total'] = count($rows);
                
                foreach ($rows as $row) {
                    // ตรวจสอบข้อมูลที่จำเป็น (national_id, first_name, last_name)
                    if (empty($row[0]) || empty($row[2]) || empty($row[3])) {
                        $result['failed']++;
                        $result['errors'][] = "แถวที่มีข้อมูล '{$row[2]} {$row[3]}' ขาดข้อมูลที่จำเป็น";
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
                    $status = strtolower($row[8] ?? 'active') === 'active' ? 'active' : 'inactive';
                    
                    // ตรวจสอบว่ามีข้อมูลครูนี้อยู่แล้วหรือไม่ (ตรวจสอบจากเลขบัตรประชาชน)
                    $stmt = $this->db->prepare("SELECT t.teacher_id FROM teachers t WHERE t.national_id = :national_id");
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
                        'status' => $status
                    ];
                    
                    // อัปเดตหรือเพิ่มข้อมูล
                    if ($existing_teacher) {
                        $this->teacher->updateTeacher($existing_teacher['teacher_id'], $teacher_data);
                        $result['updated']++;
                    } else {
                        $this->teacher->addTeacher($teacher_data);
                        $result['new']++;
                    }
                }
                
                $this->db->commit();
                $result['success'] = true;
                
            } catch (Exception $e) {
                $this->db->rollBack();
                $result['errors'][] = $e->getMessage();
            }
            
            // ลบไฟล์หลังจากใช้งานเสร็จ
            unlink($target_file);
        } else {
            $result['errors'][] = "ไม่สามารถอัปโหลดไฟล์ได้";
        }
        
        return $result;
    }
    
    /**
     * สร้างไฟล์ตัวอย่างสำหรับการนำเข้าข้อมูล
     * 
     * @return string ที่อยู่ของไฟล์ตัวอย่าง
     */
    public function createSampleFile() {
        $file_path = 'samples/teacher_template.xlsx';
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        $dir = dirname($file_path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // ตั้งค่าหัวตาราง
        $headers = [
            'เลขบัตรประชาชน',
            'คำนำหน้า',
            'ชื่อ',
            'นามสกุล',
            'แผนก',
            'ตำแหน่ง',
            'เบอร์โทรศัพท์',
            'อีเมล',
            'สถานะ'
        ];
        
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $header);
        }
        
        // จัดรูปแบบหัวตาราง
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCCCCCC');
            
        // ปรับความกว้างคอลัมน์
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(15);
        
        // เพิ่มข้อมูลตัวอย่าง
        $sample_data = [
            ['1234567890123', 'อาจารย์', 'สมชาย', 'ใจดี', 'เทคโนโลยีสารสนเทศ', 'ครูชำนาญการ', '0812345678', 'somchai@prasat.ac.th', 'active'],
            ['9876543210123', 'อาจารย์', 'สมหญิง', 'รักเรียน', 'ภาษาไทย', 'ครู', '0898765432', 'somying@prasat.ac.th', 'active']
        ];
        
        $row = 2;
        foreach ($sample_data as $data) {
            $col = 1;
            foreach ($data as $value) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $value);
            }
            $row++;
        }
        
        // บันทึกไฟล์
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($file_path);
        
        return $file_path;
    }
}
?>
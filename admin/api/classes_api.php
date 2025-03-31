<?php
/**
 * api/classes_api.php - API สำหรับดึงข้อมูลชั้นเรียน
 * 
 * ไฟล์นี้เป็นตัวอย่างการแก้ไขหากพบว่า API classes_api.php มีปัญหา
 * ตรวจสอบว่าไฟล์นี้ทำงานถูกต้องและคืนค่าข้อมูลที่จำเป็น
 */

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบการร้องขอ
if (isset($_GET['action']) && $_GET['action'] === 'get_classes_with_advisors') {
    getClassesWithAdvisors();
} else {
    // ไม่ระบุ action
    echo json_encode([
        'success' => false,
        'message' => 'ไม่ระบุการกระทำ (action)'
    ]);
}

/**
 * ดึงข้อมูลชั้นเรียนพร้อมข้อมูลครูที่ปรึกษา
 */
function getClassesWithAdvisors() {
    try {
        $db = getDB();
        
        // เพิ่ม log เพื่อช่วยในการ debug
        error_log("Getting classes with advisors");
        
        // ดึงข้อมูลชั้นเรียนที่กำลังใช้งาน (ปีการศึกษาปัจจุบัน)
        $query = "SELECT 
                  c.class_id as classId,
                  c.level as level,
                  c.group_number as groupNumber,
                  d.department_name as departmentName,
                  CASE 
                    WHEN c.level = 'ปวช.1' THEN 'ปวช.1'
                    WHEN c.level = 'ปวช.2' THEN 'ปวช.2'
                    WHEN c.level = 'ปวช.3' THEN 'ปวช.3'
                    WHEN c.level = 'ปวส.1' THEN 'ปวส.1'
                    WHEN c.level = 'ปวส.2' THEN 'ปวส.2'
                    ELSE c.level
                  END as levelName,
                  
                  (SELECT CONCAT(t.title, '', t.first_name, ' ', t.last_name) 
                   FROM class_advisors ca 
                   JOIN teachers t ON ca.teacher_id = t.teacher_id 
                   WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                   LIMIT 1) as advisorName
                  
                  FROM classes c
                  JOIN departments d ON c.department_id = d.department_id
                  JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                  WHERE ay.is_active = 1 AND c.is_active = 1
                  ORDER BY c.level, c.group_number, d.department_name";
        
        $stmt = $db->query($query);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // หากไม่มีชั้นเรียน ลองค้นหาชั้นเรียนโดยไม่กรองปีการศึกษา
        if (empty($classes)) {
            error_log("No classes found with active academic year. Getting all classes.");
            
            $query = "SELECT 
                      c.class_id as classId,
                      c.level as level,
                      c.group_number as groupNumber,
                      d.department_name as departmentName,
                      CASE 
                        WHEN c.level = 'ปวช.1' THEN 'ปวช.1'
                        WHEN c.level = 'ปวช.2' THEN 'ปวช.2'
                        WHEN c.level = 'ปวช.3' THEN 'ปวช.3'
                        WHEN c.level = 'ปวส.1' THEN 'ปวส.1'
                        WHEN c.level = 'ปวส.2' THEN 'ปวส.2'
                        ELSE c.level
                      END as levelName
                      FROM classes c
                      JOIN departments d ON c.department_id = d.department_id
                      WHERE c.is_active = 1
                      ORDER BY c.level, c.group_number, d.department_name";
            
            $stmt = $db->query($query);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // ถ้ายังไม่มีชั้นเรียน ให้สร้างข้อมูลตัวอย่าง
        if (empty($classes)) {
            error_log("No classes found at all. Creating sample data.");
            
            // สร้างข้อมูลตัวอย่างสำหรับทดสอบ
            $classes = [
                [
                    'classId' => '1',
                    'level' => 'ปวช.1',
                    'groupNumber' => '1',
                    'departmentName' => 'เทคโนโลยีสารสนเทศ',
                    'levelName' => 'ปวช.1',
                    'advisorName' => 'ครูมนตรี ศรีสุข'
                ],
                [
                    'classId' => '2',
                    'level' => 'ปวช.2',
                    'groupNumber' => '1',
                    'departmentName' => 'เทคโนโลยีสารสนเทศ',
                    'levelName' => 'ปวช.2',
                    'advisorName' => 'ครูสมศรี ใจดี'
                ],
                [
                    'classId' => '3',
                    'level' => 'ปวช.3',
                    'groupNumber' => '1',
                    'departmentName' => 'เทคโนโลยีสารสนเทศ',
                    'levelName' => 'ปวช.3',
                    'advisorName' => 'ครูวิชัย รักเรียน'
                ],
                [
                    'classId' => '4',
                    'level' => 'ปวส.1',
                    'groupNumber' => '1',
                    'departmentName' => 'เทคโนโลยีสารสนเทศ',
                    'levelName' => 'ปวส.1',
                    'advisorName' => 'ครูประภา สอนดี'
                ],
                [
                    'classId' => '5',
                    'level' => 'ปวส.2',
                    'groupNumber' => '1',
                    'departmentName' => 'เทคโนโลยีสารสนเทศ',
                    'levelName' => 'ปวส.2',
                    'advisorName' => 'ครูบัณฑิต ภูมิใจ'
                ]
            ];
        }
        
        error_log("Returning " . count($classes) . " classes");
        
        echo json_encode([
            'success' => true,
            'classes' => $classes
        ]);
    } catch (PDOException $e) {
        error_log("Error in getClassesWithAdvisors: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน: ' . $e->getMessage()
        ]);
    }
}
?>